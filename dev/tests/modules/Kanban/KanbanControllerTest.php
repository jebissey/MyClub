<?php

declare(strict_types=1);

namespace tests\modules\Kanban;

use tests\models\DataHelperTestCase;

class KanbanControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // help() sélectionne dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);
    }
}