<?php

declare(strict_types=1);

namespace tests\modules\VisitorInsights;

use tests\models\DataHelperTestCase;

class VisitorInsightsControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // helpPage() sélectionne dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // crossTab()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'Inactivated',
        ]);

        // showLastVisits() — post-migration V81 : Person → Member
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
        ]);
    }
}