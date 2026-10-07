<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\CustomFieldType;
use app\modules\PersonManager\valueObjects\CustomFieldDefinition;
use DateTimeImmutable;

final class MemberCustomFields
{
    public const SETTING_KEY = 'Member_Custom_Fields';
    private const LABEL_MAX_LENGTH = 100;
    private const TEXT_MAX_LENGTH = 1000;

    /** @return list<CustomFieldDefinition> */
    public static function parseDefinitions(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $definitions = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $definition = CustomFieldDefinition::fromArray($row);
            if ($definition !== null) {
                $definitions[] = $definition;
            }
        }
        return $definitions;
    }

    /** @param list<CustomFieldDefinition> $definitions */
    public static function encodeDefinitions(array $definitions): string
    {
        return json_encode(
            array_map(static fn(CustomFieldDefinition $d) => $d->toArray(), $definitions),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Construit les définitions à partir des lignes postées.
     * Une clé existante est conservée ; une nouvelle clé est un slug du libellé,
     * jamais en collision avec une clé existante (même supprimée, ses valeurs restent en base).
     *
     * @param array<mixed> $rows
     * @param list<CustomFieldDefinition> $existing
     * @return list<CustomFieldDefinition>
     */
    public static function buildDefinitions(array $rows, array $existing): array
    {
        $existingKeys = [];
        foreach ($existing as $definition) {
            $existingKeys[$definition->key] = true;
        }
        $usedKeys = [];
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = is_string($row['label'] ?? null) ? trim($row['label']) : '';
            $type = CustomFieldType::tryFrom(is_string($row['type'] ?? null) ? $row['type'] : '');
            if ($label === '' || $type === null) {
                continue;
            }
            $label = mb_substr($label, 0, self::LABEL_MAX_LENGTH);
            $postedKey = is_string($row['key'] ?? null) ? $row['key'] : '';
            $key = isset($existingKeys[$postedKey]) && !isset($usedKeys[$postedKey])
                ? $postedKey
                : self::uniqueKey($label, $existingKeys + $usedKeys);
            $usedKeys[$key] = true;
            $result[] = new CustomFieldDefinition($key, $label, $type);
        }
        return $result;
    }

    /** @return array<string, string|int|float> */
    public static function decodeValues(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $values = [];
        foreach ($decoded as $key => $value) {
            if (is_string($value) || is_int($value) || is_float($value)) {
                $values[(string)$key] = $value;
            }
        }
        return $values;
    }

    /** @param array<string, string|int|float> $values */
    public static function encodeValues(array $values): string
    {
        return json_encode($values === [] ? new \stdClass() : $values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Applique les valeurs postées aux champs définis ; les valeurs de clés
     * non définies (champs supprimés) sont conservées telles quelles.
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, string|int|float> $currentValues
     * @param array<mixed> $input
     * @return array<string, string|int|float>
     */
    public static function mergeValues(array $definitions, array $currentValues, array $input): array
    {
        $values = $currentValues;
        foreach ($definitions as $definition) {
            $normalized = self::normalizeValue($definition->type, $input[$definition->key] ?? null);
            if ($normalized === null) {
                unset($values[$definition->key]);
            } else {
                $values[$definition->key] = $normalized;
            }
        }
        return $values;
    }

    /**
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, string|int|float> $values
     * @return list<array{key: string, label: string, inputType: string, value: string}>
     */
    public static function toFormFields(array $definitions, array $values): array
    {
        $fields = [];
        foreach ($definitions as $definition) {
            $normalized = self::normalizeValue($definition->type, $values[$definition->key] ?? null);
            $fields[] = [
                'key' => $definition->key,
                'label' => $definition->label,
                'inputType' => $definition->type->inputType(),
                'value' => $normalized === null ? '' : (string)$normalized,
            ];
        }
        return $fields;
    }

    /**
     * Cellules d'export (une par champ défini) : nombres en nombres, dates en dates.
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, string|int|float> $values
     * @return list<string|int|float|DateTimeImmutable|null>
     */
    public static function toExportCells(array $definitions, array $values): array
    {
        $cells = [];
        foreach ($definitions as $definition) {
            $normalized = self::normalizeValue($definition->type, $values[$definition->key] ?? null);
            $cells[] = ($definition->type === CustomFieldType::Date && is_string($normalized))
                ? new DateTimeImmutable($normalized)
                : $normalized;
        }
        return $cells;
    }

    /**
     * Valeurs brutes (non vides) d'une ligne CSV, indexées par clé de champ.
     * Les dates JJ/MM/AAAA sont converties en AAAA-MM-JJ.
     *
     * @param list<CustomFieldDefinition> $definitions
     * @param array<string, int> $customMapping clé du champ => index de colonne
     * @param array<int, string|null> $row
     * @return array<string, string>
     */
    public static function extractFromRow(array $definitions, array $customMapping, array $row): array
    {
        $input = [];
        foreach ($definitions as $definition) {
            $index = $customMapping[$definition->key] ?? null;
            if ($index === null) {
                continue;
            }
            $cell = trim((string)($row[$index] ?? ''));
            if ($cell === '') {
                continue; // une cellule vide n'efface pas la valeur existante
            }
            if ($definition->type === CustomFieldType::Date) {
                $french = DateTimeImmutable::createFromFormat('!d/m/Y', $cell);
                if ($french !== false && $french->format('d/m/Y') === $cell) {
                    $cell = $french->format('Y-m-d');
                }
            }
            $input[$definition->key] = $cell;
        }
        return $input;
    }

    public static function normalizeValue(CustomFieldType $type, mixed $raw): string|int|float|null
    {
        if (!is_string($raw) && !is_int($raw) && !is_float($raw)) {
            return null;
        }
        $text = trim((string)$raw);
        if ($text === '') {
            return null;
        }
        if ($type === CustomFieldType::Number) {
            $text = str_replace(',', '.', $text);
            return is_numeric($text) ? $text + 0 : null;
        }
        if ($type === CustomFieldType::Date) {
            return self::isValidDate($text) ? $text : null;
        }
        return mb_substr($text, 0, self::TEXT_MAX_LENGTH);
    }

    private static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /** @param array<string|int, mixed> $taken */
    private static function uniqueKey(string $label, array $taken): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $ascii === false ? '' : $ascii), '_'));
        $slug = $slug === '' ? 'field' : $slug;
        $candidate = $slug;
        for ($i = 2; isset($taken[$candidate]); $i++) {
            $candidate = $slug . '_' . $i;
        }
        return $candidate;
    }
}
