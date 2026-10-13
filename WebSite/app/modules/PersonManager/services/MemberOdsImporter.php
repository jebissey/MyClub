<?php

declare(strict_types=1);

namespace app\modules\PersonManager\services;

use Throwable;
use app\helpers\MemberCustomFields;
use app\helpers\OdsReader;
use app\helpers\OdsWriter;
use app\models\LanguagesDataHelper;
use app\models\MemberImportDataHelper;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;

final class MemberOdsImporter
{
    public function __construct(
        private readonly MemberImportDataHelper $data,
        private readonly LanguagesDataHelper $languages
    ) {
    }

    /**
     * Synchronizes the members with an .ods file produced by the export.
     * - row without Id: new member; Id present several times: the row whose email matches
     *   the database (otherwise the first one) updates the member, the others are added;
     * - Id missing from the file, or Active column different from ☑: member deactivated;
     * - $dryRun: everything is executed then rolled back (preview of the counters).
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param callable(string): string $t Translator used for user-facing messages
     * @return array{created: int, updated: int, deactivated: int, reactivated: int, errors: int, messages: list<string>}
     */
    public function import(string $filePath, array $definitions, callable $t, bool $dryRun = false): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'reactivated' => 0,
            'errors' => 0,
            'messages' => [],
        ];
        $fail = static function (string $message) use (&$results): void {
            $results['errors']++;
            $results['messages'][] = $message;
        };

        $sheet = OdsReader::readFirstSheet($filePath);
        $headerLine = array_key_first($sheet);
        if ($headerLine === null) {
            $fail($t('import.ods.error.empty'));
            return $results;
        }
        $headerCells = $sheet[$headerLine];
        unset($sheet[$headerLine]);

        // Columns located by their (translated) title
        $idCol = array_search('Id', $headerCells, true);
        $activeCol = array_search($this->languages->translate('export.column.active'), $headerCells, true);
        $emailCol = array_search($this->languages->translate('import.form.email'), $headerCells, true);
        if ($idCol === false || $activeCol === false || $emailCol === false) {
            $missing = [];
            if ($idCol === false) {
                $missing[] = 'Id';
            }
            if ($activeCol === false) {
                $missing[] = $t('export.column.active');
            }
            if ($emailCol === false) {
                $missing[] = $t('import.form.email');
            }
            $fail(str_replace('{column}', implode(', ', $missing), $t('import.ods.error.missing_column')));
            return $results;
        }
        $firstNameCol = array_search($this->languages->translate('import.form.firstname'), $headerCells, true);
        $lastNameCol = array_search($this->languages->translate('import.form.lastname'), $headerCells, true);
        $phoneCol = array_search($this->languages->translate('import.form.phone'), $headerCells, true);

        $customMapping = [];
        foreach ($definitions as $definition) {
            $column = array_search($definition->label, $headerCells, true);
            if ($column !== false) {
                $customMapping[$definition->key] = $column;
            }
        }

        // Current state of the database
        /** @var array<int, array{email: string, firstName: string, lastName: string, phone: string, imported: bool, inactivated: bool, customFields: string|null}> $members */
        // Current state of the database
        $members = $this->data->getMembersForImport();
        /** @var array<string, int> $idByEmail */
        $idByEmail = [];
        foreach ($members as $memberId => $member) {
            $idByEmail[strtolower($member['email'])] = $memberId;
        }

        // First pass: read the rows, group them by Id
        /** @var list<array{line: int, id: int|null, duplicateOf: int|null, cells: array<int, string>}> $rows */
        $rows = [];
        /** @var array<int, list<int>> $rowsById */
        $rowsById = [];
        /** @var array<int, true> $seenIds */
        $seenIds = [];
        foreach ($sheet as $line => $cells) {
            $rawId = $cells[$idCol] ?? '';
            $id = null;
            if ($rawId !== '') {
                $numeric = str_replace(',', '.', $rawId);
                if (!is_numeric($numeric) || (float)$numeric < 1 || (float)$numeric !== floor((float)$numeric)) {
                    $fail(str_replace(['{line}', '{value}'], [(string)$line, $rawId], $t('import.ods.error.invalid_id')));
                    continue;
                }
                $id = (int)$numeric;
                $seenIds[$id] = true;
                $rowsById[$id][] = count($rows);
            }
            $rows[] = ['line' => $line, 'id' => $id, 'duplicateOf' => null, 'cells' => $cells];
        }
        if ($rows === []) {
            if ($results['errors'] === 0) {
                $fail($t('import.ods.error.empty'));
            }
            return $results; // no usable row: nobody gets deactivated
        }

        // Duplicate Id (copied/pasted row): a single row keeps the Id, the others become additions
        foreach ($rowsById as $id => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            $original = $indexes[0];
            $databaseEmail = isset($members[$id]) ? strtolower($members[$id]['email']) : '';
            if ($databaseEmail !== '') {
                foreach ($indexes as $index) {
                    if (strtolower(trim($rows[$index]['cells'][$emailCol] ?? '')) === $databaseEmail) {
                        $original = $index;
                        break;
                    }
                }
            }
            foreach ($indexes as $index) {
                if ($index !== $original) {
                    $rows[$index]['id'] = null;
                    $rows[$index]['duplicateOf'] = $id;
                }
            }
        }

        $this->data->beginTransaction();
        try {
            foreach ($rows as $row) {
                if (!isset($row['line'], $row['cells'])) {
                    continue;
                }
                $line = $row['line'];
                $cells = $row['cells'];
                $id = $row['id'];

                $cellEmail = trim($cells[$emailCol] ?? '');
                $validEmail = $cellEmail === '' ? false : filter_var($cellEmail, FILTER_VALIDATE_EMAIL);
                $emailKey = $validEmail === false ? '' : strtolower($validEmail);
                $firstName = $firstNameCol === false ? null : mb_substr(trim($cells[$firstNameCol] ?? ''), 0, 100);
                $lastName = $lastNameCol === false ? null : mb_substr(trim($cells[$lastNameCol] ?? ''), 0, 100);
                $phone = $phoneCol === false
                    ? null
                    : mb_substr((string)preg_replace('/[^\d\s+\-()\.]/', '', $cells[$phoneCol] ?? ''), 0, 20);
                $activeCell = str_replace("\u{FE0F}", '', trim($cells[$activeCol] ?? ''));

                if ($id !== null) {
                    // --- Existing member ---
                    if (!isset($members[$id])) {
                        $fail(str_replace(['{line}', '{id}'], [(string)$line, (string)$id], $t('import.ods.error.unknown_id')));
                        continue;
                    }
                    $current = $members[$id];

                    $newEmail = $current['email'];
                    if ($cellEmail !== '') {
                        if ($validEmail === false) {
                            $fail(str_replace(['{line}', '{email}'], [(string)$line, $cellEmail], $t('import.ods.error.invalid_email')));
                            continue;
                        }
                        // The email is the synchronization key of imported records: never modified
                        if (!$current['imported'] && strtolower($current['email']) !== $emailKey) {
                            if (isset($idByEmail[$emailKey]) && $idByEmail[$emailKey] !== $id) {
                                $fail(str_replace(
                                    ['{line}', '{email}', '{id}'],
                                    [(string)$line, $validEmail, (string)$idByEmail[$emailKey]],
                                    $t('import.ods.error.email_exists')
                                ));
                                continue;
                            }
                            $newEmail = $validEmail;
                        }
                    }
                    $newFirstName = $firstName ?? $current['firstName'];
                    $newLastName = $lastName ?? $current['lastName'];
                    $newPhone = $phone ?? $current['phone'];

                    $currentCustom = MemberCustomFields::decodeValues($current['customFields']);
                    $newCustom = $this->applyCustomFields(
                        $definitions,
                        $customMapping,
                        $cells,
                        $currentCustom,
                        $line,
                        $results['messages'],
                        $t
                    );
                    $newCustomJson = MemberCustomFields::encodeValues($newCustom);
                    $changedCustom = $newCustomJson !== MemberCustomFields::encodeValues($currentCustom);

                    $changedIdentity = $newEmail !== $current['email']
                        || $newFirstName !== $current['firstName']
                        || $newLastName !== $current['lastName']
                        || $newPhone !== $current['phone'];

                    // Active = ☑, otherwise the member is deactivated (member 1 never is)
                    $wantInactive = $id !== 1 && !self::isChecked($activeCell);

                    if ($changedIdentity) {
                        $this->data->updateIndividual($id, $newEmail, $newFirstName, $newLastName, $newPhone);
                        if (strtolower($newEmail) !== strtolower($current['email'])) {
                            unset($idByEmail[strtolower($current['email'])]);
                            $idByEmail[strtolower($newEmail)] = $id;
                        }
                    }
                    if ($changedCustom || $wantInactive !== $current['inactivated']) {
                        $this->data->updateMemberState($id, $wantInactive, $newCustomJson);
                    }
                    if ($changedIdentity || $changedCustom) {
                        $results['updated']++;
                    }
                    if ($wantInactive && !$current['inactivated']) {
                        $results['deactivated']++;
                        $results['messages'][] = '(-) ' . $newEmail;
                    } elseif (!$wantInactive && $current['inactivated']) {
                        $results['reactivated']++;
                        $results['messages'][] = '(↑) ' . $newEmail;
                    }
                    continue;
                }

                // --- New member (no Id, or duplicate row) ---
                if ($validEmail === false) {
                    $fail(str_replace(['{line}', '{email}'], [(string)$line, $cellEmail], $t('import.ods.error.invalid_email')));
                    continue;
                }
                if (isset($idByEmail[$emailKey])) {
                    $fail(str_replace(
                        ['{line}', '{email}', '{id}'],
                        [(string)$line, $validEmail, (string)$idByEmail[$emailKey]],
                        $t('import.ods.error.email_exists')
                    ));
                    continue;
                }
                $newCustom = $this->applyCustomFields(
                    $definitions,
                    $customMapping,
                    $cells,
                    [],
                    $line,
                    $results['messages'],
                    $t
                );
                $newId = $this->data->insertMember(
                    $validEmail,
                    $firstName ?? '',
                    $lastName ?? '',
                    $phone ?? '',
                    // a new member is active, unless the box is explicitly unchecked (☐)
                    $activeCell === OdsWriter::UNCHECKED,
                    MemberCustomFields::encodeValues($newCustom)
                );
                $idByEmail[$emailKey] = $newId;
                $results['created']++;
                $results['messages'][] = '(+) ' . $validEmail;
                if ($row['duplicateOf'] !== null) {
                    $results['messages'][] = str_replace(
                        ['{line}', '{id}'],
                        [(string)$line, (string)$row['duplicateOf']],
                        $t('import.ods.warning.duplicate_id')
                    );
                }
            }

            // Ids missing from the file: members deactivated
            foreach ($members as $memberId => $member) {
                if ($memberId === 1 || isset($seenIds[$memberId]) || $member['inactivated']) {
                    continue;
                }
                $this->data->deactivateMember($memberId);
                $results['deactivated']++;
                $results['messages'][] = '(-) ' . $member['email'];
            }

            if ($dryRun) {
                $this->data->rollBack();
            } else {
                $this->data->commit();
            }
        } catch (Throwable $e) {
            if ($this->data->inTransaction()) {
                $this->data->rollBack();
            }
            throw $e;
        }

        return $results;
    }

    /**
     * Applies the custom fields of a row: empty cell = value cleared,
     * invalid value = reported and current value kept, missing column = field untouched.
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, int> $customMapping
     * @param array<int, string> $cells
     * @param array<string, string|int|float> $current
     * @param list<string> $messages
     * @param callable(string): string $t
     * @return array<string, string|int|float>
     */
    private function applyCustomFields(
        array $definitions,
        array $customMapping,
        array $cells,
        array $current,
        int $line,
        array &$messages,
        callable $t
    ): array {
        if ($customMapping === []) {
            return $current;
        }
        $extracted = MemberCustomFields::extractFromRow($definitions, $customMapping, $cells);
        $applicable = [];
        $input = [];
        foreach ($definitions as $definition) {
            if (!isset($customMapping[$definition->key])) {
                continue;
            }
            if (isset($extracted[$definition->key])) {
                $raw = $extracted[$definition->key];
                if (MemberCustomFields::normalizeValue($definition->type, $raw) === null) {
                    $messages[] = str_replace(
                        ['{line}', '{value}', '{field}'],
                        [(string)$line, $raw, $definition->label],
                        $t('import.error.invalid_custom_value')
                    );
                    continue;
                }
                $input[$definition->key] = $raw;
            }
            $applicable[] = $definition;
        }
        return MemberCustomFields::mergeValues($applicable, $current, $input);
    }

    private static function isChecked(string $cell): bool
    {
        return $cell === OdsWriter::CHECKED || $cell === '✅';
    }
}
