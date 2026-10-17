<?php

declare(strict_types=1);

namespace test\CodingStandards\Analysis;

final readonly class ValueObjectReferenceAnalyzer
{
    // Matches app\modules\<Module>\valueObjects\<Name> and the grouped form ...\valueObjects\{A, B}
    private const REFERENCE_PATTERN = '~app\\\\modules\\\\(\w+)\\\\valueObjects\\\\(?:(\w+)|\{([^}]*)\})~';

    /**
     * Returns the module a file belongs to (modules/<Module>/...), or null outside modules.
     */
    public function moduleOf(string $relativePath): ?string
    {
        return preg_match('#^modules/([^/]+)/#', $relativePath, $m) === 1 ? $m[1] : null;
    }

    /**
     * @return list<array{module: string, name: string}> Unique ValueObject references found in the source
     */
    public function references(string $source): array
    {
        if (preg_match_all(self::REFERENCE_PATTERN, $source, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $found = [];
        foreach ($matches as $match) {
            $group = $match[3] ?? '';
            $names = $group !== '' ? explode(',', $group) : [$match[2] ?? ''];

            foreach ($names as $name) {
                // Strip "as Alias" from grouped imports
                $name = trim((string) preg_replace('~\s+as\s+.*$~i', '', $name));
                if ($name === '') {
                    continue;
                }
                $found["{$match[1]}\\{$name}"] = ['module' => $match[1], 'name' => $name];
            }
        }

        return array_values($found);
    }
}
