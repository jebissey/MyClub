<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class ObjectShapeDocblockRule implements RuleInterface
{
    /** Shapes with this many properties or fewer are tolerated. */
    private const MAX_INLINE_PROPERTIES = 10;

    private const OPENERS = ['{' => '}', '<' => '>', '(' => ')', '[' => ']'];

    public function __construct(private SourcePaths $paths) {}

    public function label(): string
    {
        return 'Inline object{...} shapes with several properties in docblocks (use a ValueObject instead)';
    }

    /**
     * @param list<ClassInfo> $classes
     * @return list<string>
     */
    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            $source = $class->sourceCode;
            $count = preg_match_all('~\bobject\s*\{~', $source, $matches, PREG_OFFSET_CAPTURE);
            if ($count === false || $count === 0) {
                continue;
            }

            foreach ($matches[0] as [$text, $offset]) {
                $position = (int) $offset;

                if (!$this->isInDocblock($source, $position)) {
                    continue;
                }

                $properties = $this->countProperties($source, $position + strlen($text));

                if ($properties > self::MAX_INLINE_PROPERTIES) {
                    $issues[] = sprintf(
                        '%s:%d inline object{...} shape with %d properties, replace it with a ValueObject',
                        $this->paths->rel($class),
                        substr_count($source, "\n", 0, $position) + 1,
                        $properties,
                    );
                }
            }
        }

        return $issues;
    }

    private function isInDocblock(string $source, int $offset): bool
    {
        $lineStart = strrpos(substr($source, 0, $offset), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $prefix = substr($source, $lineStart, $offset - $lineStart);

        return preg_match('~^\s*(?:/\*\*|\*)~', $prefix) === 1;
    }

    /**
     * Counts the top-level properties of a shape, starting right after its opening brace.
     */
    private function countProperties(string $source, int $start): int
    {
        $depth = 0;
        $properties = 0;
        $hasContent = false;
        $length = strlen($source);

        for ($i = $start; $i < $length; $i++) {
            $char = $source[$i];

            if (isset(self::OPENERS[$char])) {
                $depth++;
                $hasContent = true;
            } elseif ($depth === 0 && $char === '}') {
                break;
            } elseif ($char === '}' || $char === '>' || $char === ')' || $char === ']') {
                $depth--;
            } elseif ($depth === 0 && $char === ',') {
                if ($hasContent) {
                    $properties++;
                }
                $hasContent = false;
            } elseif (!ctype_space($char) && $char !== '*') {
                $hasContent = true;
            }
        }

        return $hasContent ? $properties + 1 : $properties;
    }
}
