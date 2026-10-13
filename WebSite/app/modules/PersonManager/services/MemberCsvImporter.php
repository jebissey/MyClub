<?php

declare(strict_types=1);

namespace app\modules\PersonManager\services;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use app\helpers\MemberCustomFields;
use app\models\MemberImportDataHelper;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;

/**
 * @phpstan-type CsvColumnMapping array{
 *     email: int|string,
 *     firstName: int|string,
 *     lastName: int|string,
 *     phone: int|string,
 *     custom?: array<string, int>
 * }
 * @phpstan-type CsvImportResults array{
 *     created: int,
 *     updated: int,
 *     deactivated: int,
 *     errors: int,
 *     processedEmails: list<string>,
 *     messages: list<string>
 * }
 */
final class MemberCsvImporter
{
    public function __construct(private readonly MemberImportDataHelper $memberImportDataHelper)
    {
    }

    /**
     * Imports members from a CSV file (upsert on the email), then deactivates the
     * existing members that are absent from the file.
     *
     * A row without a valid email is still imported, with a deterministic fictive address.
     *
     * @param CsvColumnMapping $mapping
     * @param array<string, int> $existingPersons Member ids indexed by lower-cased email
     * @param list<CustomFieldDefinition> $definitions
     * @param callable(string): string $t Translator used for user-facing messages
     * @return CsvImportResults
     */
    public function import(
        string $filePath,
        int $headerRow,
        array $mapping,
        array $existingPersons,
        array $definitions,
        callable $t
    ): array {
        foreach (['email', 'firstName', 'lastName', 'phone'] as $key) {
            if (!array_key_exists($key, $mapping)) {
                throw new InvalidArgumentException("Missing mapping key: {$key}");
            }
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException("CSV file not found or not readable: $filePath");
        }
        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new RuntimeException("Unable to open the CSV file: $filePath");
        }

        $results = [
            'created'         => 0,
            'updated'         => 0,
            'deactivated'     => 0,
            'errors'          => 0,
            'processedEmails' => [],
            'messages'        => [],
        ];

        $definitionsByKey = [];
        foreach ($definitions as $definition) {
            $definitionsByKey[$definition->key] = $definition;
        }
        $customMapping = $mapping['custom'] ?? [];

        $this->memberImportDataHelper->beginTransaction();

        try {
            $processedEmailKeys = [];
            $currentRow = 0;
            while (($data = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $currentRow++;
                if ($currentRow <= $headerRow) {
                    continue;
                }
                $rawEmail  = trim((string)($data[$mapping['email']] ?? ''));
                $firstName = mb_substr(trim((string)($data[$mapping['firstName']] ?? '')), 0, 100);
                $lastName  = mb_substr(trim((string)($data[$mapping['lastName']] ?? '')), 0, 100);
                $phoneRaw  = preg_replace('/[^\d\s+\-()\.]/', '', (string)($data[$mapping['phone']] ?? ''));
                $phone     = mb_substr(is_string($phoneRaw) ? $phoneRaw : '', 0, 20);

                $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    if ($rawEmail === '' && $firstName === '' && $lastName === '' && $phone === '') {
                        continue;
                    }
                    $email = self::fictiveEmail($firstName, $lastName, $phone, $rawEmail);
                    $results['messages'][] = str_replace(
                        ['{line}', '{email}'],
                        [(string)$currentRow, $email],
                        $t('import.warning.fictive_email')
                    );
                }

                $personData = [
                    'email'     => $email,
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                    'phone'     => $phone,
                ];

                $emailKey   = strtolower($personData['email']);
                $existingId = $existingPersons[$emailKey] ?? null;

                $id = $this->memberImportDataHelper->upsertIndividualByEmail($personData);
                // Prefer the pre-loaded id when available (same result after ON CONFLICT)
                if ($existingId !== null) {
                    $id = $existingId;
                }

                $this->memberImportDataHelper->upsertMemberAsImported($id);

                if ($definitions !== [] && $customMapping !== []) {
                    $rawCustom = MemberCustomFields::extractFromRow($definitions, $customMapping, $data);
                    /** @var array<string, string|int|float> $importedCustom */
                    $importedCustom = [];
                    foreach ($rawCustom as $key => $raw) {
                        $definition = $definitionsByKey[$key];
                        $normalized = MemberCustomFields::normalizeValue($definition->type, $raw);
                        if ($normalized === null) {
                            $results['messages'][] = str_replace(
                                ['{line}', '{value}', '{field}'],
                                [(string)$currentRow, $raw, $definition->label],
                                $t('import.error.invalid_custom_value')
                            );
                            continue;
                        }
                        $importedCustom[$key] = $normalized;
                    }
                    if ($importedCustom !== []) {
                        $json = $this->memberImportDataHelper->getCustomFieldsJson($id);
                        $current = MemberCustomFields::decodeValues($json);
                        $this->memberImportDataHelper->setCustomFieldsJson(
                            $id,
                            MemberCustomFields::encodeValues($importedCustom + $current)
                        );
                    }
                }

                if ($existingId !== null) {
                    $results['updated']++;
                } else {
                    $results['created']++;
                    $results['messages'][] = '(+) ' . $personData['email'];
                    $existingPersons[$emailKey] = $id;
                }

                $processedEmailKeys[$emailKey] = true;
                $results['processedEmails'][]  = $personData['email'];
            }

            $idsToDeactivate = [];
            foreach ($existingPersons as $emailKey => $id) {
                if (!isset($processedEmailKeys[$emailKey]) && $id !== 1) {
                    $idsToDeactivate[] = $id;
                }
            }
            $results['deactivated'] = $this->memberImportDataHelper->deactivateMembers($idsToDeactivate);

            $this->memberImportDataHelper->commit();
        } catch (Throwable $e) {
            $this->memberImportDataHelper->rollBack();
            throw $e;
        } finally {
            fclose($file);
        }

        return $results;
    }

    private static function fictiveEmail(string $firstName, string $lastName, string $phone, string $rawEmail): string
    {
        $seed = ($firstName !== '' || $lastName !== '')
            ? mb_strtolower($firstName . '|' . $lastName)
            : mb_strtolower($rawEmail . '|' . $phone);

        return substr(sha1($seed), 0, 32) . '@myclub.foo';
    }
}
