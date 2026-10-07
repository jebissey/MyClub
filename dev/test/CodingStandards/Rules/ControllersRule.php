<?php

declare(strict_types=1);

namespace test\CodingStandards\Rules;

use test\CodingStandards\Analysis\TerminalCallAnalyzer;
use test\CodingStandards\SourcePaths;

final readonly class ControllersRule implements RuleInterface
{
    // Public methods exempt from the "must end with a terminal call" rule.
    private const RENDER_EXEMPT_METHODS = ['__construct', '__destruct', 'render'];

    public function __construct(private SourcePaths $paths, private TerminalCallAnalyzer $analyzer)
    {
    }

    public function label(): string
    {
        return 'Controllers misplaced/badly suffixed or methods without render';
    }

    public function check(array $classes): array
    {
        $issues = [];

        foreach ($classes as $class) {
            if ($class->kind !== 'class' || !$this->paths->isModuleRootFile($class)) {
                continue;
            }

            $file = $this->paths->rel($class);

            if (!str_ends_with($class->className, 'Controller')) {
                $issues[] = "{$class->className} ({$file}) is at a module root: must be suffixed 'Controller'";
                continue;
            }

            foreach ($this->analyzer->analyzePublicMethods($class->sourceCode) as $method) {
                if (in_array($method['name'], self::RENDER_EXEMPT_METHODS, true)) {
                    continue;
                }

                if (!$method['endsWithAcceptedTerminalCall']) {
                    $issues[] = "{$class->className}::{$method['name']}() ({$file}) does not end with render(...), a redirect, a file send, a raise() or a direct echo";
                }
            }
        }

        return $issues;
    }
}