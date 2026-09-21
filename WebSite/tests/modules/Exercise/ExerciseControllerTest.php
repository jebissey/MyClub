<?php

declare(strict_types=1);

namespace tests\modules\Exercise;

use tests\models\DataHelperTestCase;

class ExerciseControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Exercise : set() dans create() et save(), get() dans edit(), save() et play()
        $this->assertColumnsExist($pdo, 'Exercise', [
            'Id',
            'Title',
            'Detail',
            'Content',
            'CreatedBy',
            'LastUpdate',
        ]);

        // NOTE : index() délègue à exerciseTableDataHelper->getQuery(), à couvrir
        // séparément dans ExerciseTableDataHelperTest si elle requête d'autres
        // colonnes (PersonName/GroupName laissent supposer une jointure
        // Individual/Member + Group).
    }
}