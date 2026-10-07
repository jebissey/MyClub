<?php

declare(strict_types=1);

namespace test\CodingStandards;

use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class SourcePaths
{
    public const VALUE_OBJECTS = 'valueObjects';
    public const VIEW_MODELS = 'viewModels';
    public const APIS = 'apis';
    public const MODULES = 'modules';

    public function __construct(private string $appDir)
    {
    }

    public function rel(ClassInfo $class): string
    {
        return $class->relativePath($this->appDir);
    }

    public function inSubpath(ClassInfo $class, string $subpath): bool
    {
        $relative = $this->rel($class);

        return $relative === $subpath
            || str_starts_with($relative, "{$subpath}/")
            || str_contains($relative, "/{$subpath}/");
    }

    public function isModuleRootFile(ClassInfo $class): bool
    {
        return (bool) preg_match(
            '#^' . preg_quote(self::MODULES, '#') . '/[^/]+/[^/]+\.php$#',
            $this->rel($class),
        );
    }
}