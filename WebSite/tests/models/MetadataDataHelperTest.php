<?php

declare(strict_types=1);

namespace tests\models;

class MetadataDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Metadata', [
            'Id',
            'ThisIsTestSite',
            'ThisIsForcedLanguage',
            'ThisIsProdSiteUrl',
        ]);
    }
}