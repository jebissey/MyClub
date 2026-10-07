<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\SourcePaths;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class ApisRule implements RuleInterface
{
    public function __construct(private SourcePaths $paths)
    {
    }

    public function label(): string
    {
        return 'JSON classes misplaced/badly suffixed';
    }

    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class') {
                continue;
            }

            $looksLikeApi = $this->emitsJson($class);
            $inApisFolder = $this->paths->inSubpath($class, SourcePaths::APIS);
            $suffixedApi = str_ends_with($class->className, 'Api');
            $where = "{$class->className} ({$this->paths->rel($class)})";

            if ($looksLikeApi && !$suffixedApi) {
                $issues[] = "{$where} seems to return JSON: must be suffixed 'Api'";
            }

            if ($looksLikeApi && !$inApisFolder) {
                $issues[] = "{$where} seems to return JSON: must be in app/" . SourcePaths::APIS;
            }

            if ($inApisFolder && !$suffixedApi) {
                $issues[] = "{$where} is in app/" . SourcePaths::APIS . ": must be suffixed 'Api'";
            }
        }

        return $issues;
    }

    private function emitsJson(ClassInfo $class): bool
    {
        // Tight signal on actual JSON responses (Flight), rather than a raw
        // json_encode() which also matches internal serialization (logs,
        // storage) unrelated to an HTTP response.
        return str_contains($class->sourceCode, 'Flight::json(')
            || str_contains($class->sourceCode, '->json(');
    }
}