<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class ArticleApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Order', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
        ]);

        $this->assertColumnsExist($pdo, 'OrderReply', [
            'IdOrder',
            'IdPerson',
            'Answers',
        ]);

        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
        ]);

        $this->assertColumnsExist($pdo, 'Reply', [
            'IdSurvey',
            'IdPerson',
            'Answers',
        ]);

        $this->assertColumnsExist($pdo, 'DesignVote', [
            'Id',
            'IdDesign',
            'IdPerson',
            'Vote',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
        ]);
    }
}