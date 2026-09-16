<?php

declare(strict_types=1);

namespace tests\apis;

use tests\models\DataHelperTestCase;

class CarouselApiTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Carousel', [
            'Id',
            'IdArticle',
            // colonne qui stocke le contenu HTML de l’item
            // (nom exact à confirmer dans CarouselDataHelper::addOrUpdate)
            // exemples possibles : 'Item', 'Content', 'Html', 'Text'…
        ]);

        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [  
            'Id',
        ]);
    }
}