<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use test\CodingStandards\CodingStandardsChecker;

function main(): int
{
    require_once MYCLUB_WEBSITE_DIR . '/vendor/autoload.php';

    $appDir = realpath(MYCLUB_WEBSITE_DIR . '/app');
    if ($appDir === false) {
        fwrite(STDERR, "Répertoire app/ introuvable.\n");
        return 1;
    }

    $violations = (new CodingStandardsChecker($appDir))->check();

    if ($violations === []) {
        fwrite(STDERR, "✅ Toutes les classes respectent les conventions.\n");
        return 0;
    }

    foreach ($violations as $rule => $items) {
        fwrite(STDERR, "❌ {$rule} :\n");
        foreach ($items as $item) {
            fwrite(STDERR, "  - {$item}\n");
        }
        fwrite(STDERR, "\n");
    }

    return 1;
}

exit(main());