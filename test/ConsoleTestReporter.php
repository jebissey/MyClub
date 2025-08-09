<?php

class ConsoleTestReporter implements TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void
    {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "RÉSUMÉ DES TESTS\n";
        echo str_repeat('=', 80) . "\n";
        echo "Total des tests exécutés: {$summary->totalTests}\n";
        echo "Succès (2xx-3xx): {$summary->successful}\n";
        echo "Erreurs HTTP (5xx): {$summary->errors}\n";
        echo "Autres: " . ($summary->totalTests - $summary->successful - $summary->errors) . "\n";

        if ($summary->hasDatabase) {
            echo "\n" . str_repeat('=', 80) . "\n";
            echo "ERREURS DE VALIDATION:";
            echo "\n" . str_repeat('=', 80) . "\n";
            echo "Erreurs de paramètres: " . count($summary->parameterErrors) . "\n";
            echo "Erreurs de réponse: " . count($summary->responseErrors) . "\n";
            echo "Erreurs d'authentification: " . count($summary->testErrors) . "\n";
        }

        echo "\nRépartition par code de statut:\n";
        foreach ($summary->statusCodes as $code => $count) {
            $color = $this->getStatusColor($code);
            echo sprintf("  %s%d%s: %d\n", $color, $code, "\033[0m", $count);
        }

        if ($summary->hasDatabase) {
            if (!empty($summary->responseErrors)) {
                echo "\nERREURS DE RÉPONSE:\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($summary->responseErrors as $error) {
                    echo "  • $error\n";
                }
            }

            if (!empty($summary->testErrors)) {
                echo "\nERREURS D'AUTHENTIFICATION:\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($summary->testErrors as $error) {
                    echo "  • $error\n";
                }
            }

            if (!empty($summary->parameterErrors)) {
                echo "\nERREURS DE PARAMÈTRES:\n";
                echo str_repeat('-', 80) . "\n";
                foreach ($summary->parameterErrors as $error) {
                    echo "  • $error\n";
                }
            }
        }
    }

    private function getStatusColor(int $code): string
    {
        if ($code >= 200 && $code < 300) return "\033[32m"; // Vert
        if ($code >= 300 && $code < 400) return "\033[33m"; // Jaune
        if ($code >= 400 && $code < 500) return "\033[35m"; // Magenta
        if ($code >= 500) return "\033[31m"; // Rouge
        return "\033[37m"; // Blanc
    }
}
