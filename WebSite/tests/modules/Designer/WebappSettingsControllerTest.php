<?php

declare(strict_types=1);

namespace tests\modules\Designer;

use tests\models\DataHelperTestCase;

class WebappSettingsControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // editSettings() / saveSettings() sélectionnent dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // editSettings() / saveSettings() via Settings (valeurs numériques et couleurs)
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);
    }
}