<?php

declare(strict_types=1);

namespace test\CodingStandards;

use test\CodingStandards\Analysis\PropertyAnalyzer;
use test\CodingStandards\Analysis\TerminalCallAnalyzer;
use test\CodingStandards\Rules\ApisRule;
use test\CodingStandards\Rules\ControllersRule;
use test\CodingStandards\Rules\FinalClassesRule;
use test\CodingStandards\Rules\FinalReadonlyRule;
use test\CodingStandards\Rules\ReadonlyPropertiesRule;
use test\CodingStandards\Rules\RuleInterface;
use test\CodingStandards\ValueObjects\ClassInfo;

final readonly class CodingStandardsChecker
{
    /**
     * @param list<RuleInterface> $rules
     * @param list<ClassInfo> $classes
     */
    public function __construct(private array $rules, private array $classes)
    {
    }

    public static function create(string $appDir): self
    {
        $paths = new SourcePaths($appDir);
        $dedicated = [SourcePaths::VALUE_OBJECTS, SourcePaths::VIEW_MODELS];

        return new self(
            [
                new FinalReadonlyRule($paths, SourcePaths::VALUE_OBJECTS, 'ValueObjects not final readonly'),
                new FinalReadonlyRule($paths, SourcePaths::VIEW_MODELS, 'ViewModels not final readonly or badly suffixed', 'ViewModel'),
                new FinalClassesRule($paths, $dedicated),
                new ControllersRule($paths, new TerminalCallAnalyzer()),
                new ApisRule($paths),
                new ReadonlyPropertiesRule($paths, new PropertyAnalyzer(), $dedicated),
            ],
            (new ClassScanner())->scan($appDir),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function check(): array
    {
        $violations = [];

        foreach ($this->rules as $rule) {
            $issues = $rule->check($this->classes);
            if ($issues !== []) {
                $violations[$rule->label()] = $issues;
            }
        }

        return $violations;
    }
}