<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\TestCoverage\TestCoverageChecker;
use app\models\Data;

function main(): int
{
    require_once MYCLUB_WEBSITE_DIR . '/vendor/autoload.php';

    $projectRoot = realpath(MYCLUB_ROOT);
    $appDir = realpath(MYCLUB_WEBSITE_DIR . '/app');
    $testsDir = realpath(MYCLUB_ROOT . '/dev/tests');
    if ($projectRoot === false || $appDir === false || $testsDir === false) {
        fwrite(STDERR, "Répertoire app/ ou tests/ introuvable.\n");
        return 1;
    }

    $checker = new TestCoverageChecker($appDir, $testsDir, Data::class);
    $missing = $checker->findMissing();
    $uncovered = $checker->findUncovered();

    if ($missing !== []) {
        fwrite(STDERR, "❌ Classes without a unit test (inheritance or DataHelper usage):\n\n");
        foreach ($missing as $m) {
            $relative = ltrim(substr($m->expectedTestPath, strlen($projectRoot)), '/');
            fwrite(STDERR, "  - {$m->className}\n    -> {$relative}\n\n");
        }
    }

    if ($uncovered !== []) {
        fwrite(STDERR, "❌ Tests not covering all queried tables/columns:\n\n");
        foreach ($uncovered as $u) {
            $relative = ltrim(substr($u->testPath, strlen($projectRoot)), '/');
            $cols = $u->columns === [] ? '(table not asserted)' : implode(', ', $u->columns);
            fwrite(STDERR, "  - {$u->className}  [{$u->table}] missing: {$cols}\n    -> {$relative}\n\n");
        }
    }

    if ($missing !== [] || $uncovered !== []) {
        return 1;
    }

    fwrite(STDERR, "✅ All relevant classes have complete tests.\n");
    return 0;
}

exit(main());
