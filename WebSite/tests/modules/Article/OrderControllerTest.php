<?php

declare(strict_types=1);

namespace tests\modules\Article;

use tests\models\DataHelperTestCase;

class OrderControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Article : get('Article', ..., 'Title, Id') dans add(),
        // get('Article', ..., 'Id') et get('Article', ..., 'Id, Title, Content,
        // CreatedBy, PublishedBy, IdGroup, OnlyForMembers, LastUpdate') dans viewResults()
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

        // Order : get/set dans add(), createOrUpdate()
        $this->assertColumnsExist($pdo, 'Order', [
            'Id',
            'IdArticle',
            'Question',
            'Options',
            'ClosingDate',
            'Visibility',
        ]);

        // OrderReply : gets('OrderReply', ..., '*') dans viewResults(), dont le
        // résultat est ensuite mappé sur OrderReplyRow (Id, IdPerson, IdOrder,
        // Answers, LastUpdate)
        $this->assertColumnsExist($pdo, 'OrderReply', [
            'Id',
            'IdPerson',
            'IdOrder',
            'Answers',
            'LastUpdate',
        ]);

        // Individual : get('Individual', ['Id' => $reply->IdPerson], 'FirstName, LastName')
        // dans viewResults() — IdPerson référence désormais Member, mais Individual.Id
        // et Member.Id partagent la même valeur, donc l'appel reste correct tel quel.
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'FirstName',
            'LastName',
        ]);

        // NOTE : orderDataHelper->getWithCreator() n'est pas défini dans ce fichier ;
        // à couvrir séparément dans OrderDataHelperTest si elle requête Person/Individual/Member.
    }
}