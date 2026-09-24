<?php

declare(strict_types=1);

namespace tests\modules\Communication;

use tests\models\DataHelperTestCase;

class CommunicationControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // helpCommunication() sélectionne dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);
    }
}
