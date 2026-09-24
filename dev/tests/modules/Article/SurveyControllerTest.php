<?php

declare(strict_types=1);

namespace tests\modules\Article;

use tests\models\DataHelperTestCase;

class SurveyControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // add() / createOrUpdate() / viewResults()
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'Content',
            'CreatedBy',
            'PublishedBy',
            'IdGroup',
            'OnlyForMembers',
            'LastUpdate',
        ]);

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
            'ClosingDate',
            'Visibility',
        ]);

        // viewResults() récupère le nom via Individual (post-migration V81)
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
        ]);
    }
}