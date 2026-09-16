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

        // Table / colonnes utilisées par DesignDataHelper::insertOrUpdate()
        // (designId + vote + Id de la personne connectée)
        $this->assertColumnsExist($pdo, 'Design', [
            'Id',
            // adaptez le nom exact de la colonne de vote si différent
            // (ex. 'Vote', 'Choice', 'Value'…)
        ]);

        // Tables / colonnes utilisées par OrderReplyDataHelper::insertOrUpdate()
        // et ReplyDataHelper::insertOrUpdate()
        // (déjà couvertes ci-dessus par OrderReply et Reply)

        // Personne connectée (Id utilisé partout)
        $this->assertColumnsExist($pdo, 'Individual', [  
            'Id',
        ]);
    }
}