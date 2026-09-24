<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\TestCoverage\TestCoverageChecker;
use app\models\Data;

function main(): int
{
    require_once __DIR__ . '/../WebSite/vendor/autoload.php';

    $projectRoot = MYCLUB_ROOT;
    $appDir = realpath(MYCLUB_WEBSITE_DIR . '/app');
    $testsDir = realpath(MYCLUB_WEBSITE_DIR . '/tests');
    if ($projectRoot === false || $appDir === false || $testsDir === false) {
        fwrite(STDERR, "Répertoire app/ ou tests/ introuvable.\n");
        return 1;
    }

    $missing = (new TestCoverageChecker($appDir, $testsDir, Data::class))->findMissing();

    if ($missing !== []) {
        fwrite(STDERR, "❌ Classes sans test unitaire (héritage ou injection de DataHelper) :\n\n");
        foreach ($missing as $m) {
            $relative = ltrim(substr($m->expectedTestPath, strlen($projectRoot)), '/');
            fwrite(STDERR, "  - {$m->className}\n");
            fwrite(STDERR, "    -> {$relative}\n\n");
        }
        return 1;
    }

    fwrite(STDERR, "✅ Toutes les classes concernées ont leur test.\n");
    return 0;
}

exit(main());