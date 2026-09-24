<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class HomeControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // home() via getDefaultColors() / getSetting() et Settings directes
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // home() / helpHome() / legalNotice() sélectionnent dynamiquement la colonne de langue courante
        // (ex. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // home() récupère le contenu de l'article de pied de page
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'Content',
        ]);

        // home() récupère les items du carrousel
        $this->assertColumnsExist($pdo, 'Carousel', [
            'IdArticle',
            'Item',
        ]);
    }
}