<?php

class ConsoleTestReporter implements TestReporterInterface
{
    private const COLOR_GREEN   = "\033[32m";
    private const COLOR_YELLOW  = "\033[33m";
    private const COLOR_MAGENTA = "\033[35m";
    private const COLOR_RED     = "\033[31m";
    private const COLOR_WHITE   = "\033[37m";
    private const COLOR_RESET   = "\033[0m";

    public function displaySummary(TestSummary $summary): void
    {
        echo $this->formatSummary($summary);

        echo "\nRépartition par code de statut:\n";
        foreach ($summary->statusCodes as $code => $count) {
            $color = $this->getStatusColor($code);
            echo sprintf("  %s%d%s: %d\n", $color, $code, self::COLOR_RESET, $count);
        }

        if ($summary->hasDatabase) {
            $this->displayErrorSection("ERREURS DE RÉPONSE", $summary->responseErrors);
            $this->displayErrorSection("ERREURS D'AUTHENTIFICATION", $summary->testErrors);
            $this->displayErrorSection("ERREURS DE PARAMÈTRES", $summary->parameterErrors);
        }
    }

    private function formatSummary(TestSummary $summary): string
    {
        $out = [];
        $out[] = "\n" . str_repeat('=', 80);
        $out[] = "RÉSUMÉ DES TESTS";
        $out[] = str_repeat('=', 80);
        $out[] = "Total des tests exécutés: {$summary->totalTests}";
        $out[] = "Succès (2xx-3xx): {$summary->successful}";
        $out[] = "Erreurs HTTP (5xx): {$summary->errors}";
        $out[] = "Autres: " . ($summary->totalTests - $summary->successful - $summary->errors);

        if ($summary->hasDatabase) {
            $out[] = "\n" . str_repeat('=', 80);
            $out[] = "ERREURS DE VALIDATION:";
            $out[] = str_repeat('=', 80);
            $out[] = "Erreurs de paramètres: " . count($summary->parameterErrors);
            $out[] = "Erreurs de réponse: " . count($summary->responseErrors);
            $out[] = "Erreurs d'authentification: " . count($summary->testErrors);
        }

        return implode("\n", $out) . "\n";
    }

    private function displayErrorSection(string $title, array $errors): void
    {
        if (!empty($errors)) {
            echo "\n$title:\n";
            echo str_repeat('-', 80) . "\n";
            foreach ($errors as $error) {
                echo "  • $error\n";
            }
        }
    }

    private function getStatusColor(int $code): string
    {
        return match (true) {
            $code >= 200 && $code < 300 => self::COLOR_GREEN,
            $code >= 300 && $code < 400 => self::COLOR_YELLOW,
            $code >= 400 && $code < 500 => self::COLOR_MAGENTA,
            $code >= 500                 => self::COLOR_RED,
            default                      => self::COLOR_WHITE
        };
    }
}

