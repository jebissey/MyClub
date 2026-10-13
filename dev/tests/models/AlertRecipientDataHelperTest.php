<?php

declare(strict_types=1);

namespace tests\models;

class AlertRecipientDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // getPersonWantedToBeAlerted(): $this->get('Article', ['Id' => ...], 'IdGroup')
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'IdGroup',
        ]);

        // getPersonWantedToBeAlerted(): $this->get('Survey', ['IdArticle' => ...], 'Id')
        $this->assertColumnsExist($pdo, 'Survey', [
            'Id',
            'IdArticle',
        ]);

        // getPersonWantedToBeAlerted(): $this->get('Order', ['IdArticle' => ...], 'Id')
        $this->assertColumnsExist($pdo, 'Order', [
            'Id',
            'IdArticle',
        ]);

        // getPersonWantedToBeAlerted(): $this->set('Message', [...])
        $this->assertColumnsExist($pdo, 'Message', [
            'EventId',
            'PersonId',
            'Text',
            'From',
        ]);
    }
}
