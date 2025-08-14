<?php

class ConsoleTestReporter implements TestReporterInterface
{
    public function displaySummary(TestSummary $summary): void
    {
        echo $this->formatSummary($summary);

        echo "\nRépartition par code de statut:\n";
        foreach ($summary->statusCodes as $code => $count) {
            $color = $this->getStatusColor($code);
            echo sprintf("  %s%d%s: %d\n", $color, $code, Color::Reset->value, $count);
        }

        if ($summary->hasDatabase) {
            $this->displayErrorSection("ERREURS DE RÉPONSE", $summary->responseErrors);
            $this->displayErrorSection("ERREURS D'AUTHENTIFICATION", $summary->testErrors);
            $this->displayErrorSection("ERREURS DE PARAMÈTRES", $summary->parameterErrors);
        }
    }

    public function sectionTitle(string $title): void
    {
        echo str_repeat('-', 80) . PHP_EOL;
        echo $title . PHP_EOL;
    }

    public function error(string $message): string
    {
        echo Color::Red->value . "ERROR: {$message}" . Color::Reset->value . PHP_EOL;
        return "ERROR: {$message}";
    }

    public function validationErrors(array $errors): array
    {
        $errors = [];
        foreach ($errors as $err) {
            $errors[] =  $this->error($err);
        }
        return $errors;
    }

    #region Private functions
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
            echo str_repeat('=', 80);
            echo "\n$title:\n";
            echo str_repeat('=', 80) . "\n";
            foreach ($errors as $error) {
                echo "  • $error\n";
            }
        }
    }

    private function getStatusColor(int $code): string
    {
        return match (true) {
            $code >= 200 && $code < 300 => Color::Green->value,
            $code >= 300 && $code < 400 => Color::Yellow->value,
            $code >= 400 && $code < 500 => Color::Magenta->value,
            $code >= 500                => Color::Red->value,
            default                     => Color::White->value,
        };
    }
}
