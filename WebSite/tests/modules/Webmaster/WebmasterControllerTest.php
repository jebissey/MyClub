<?php

declare(strict_types=1);

namespace tests\modules\Webmaster;

use tests\models\DataHelperTestCase;

class WebmasterControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // clubCustomizationEdit() / clubCustomizationSave() via getSetting() / setSetting()
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // helpAdmin() / helpWebmaster() sélectionnent dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);
    }
}
