<?php

declare(strict_types=1);

namespace tests\modules\User;

use tests\models\DataHelperTestCase;

class HomeControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // home() via getNavbarColors() / getSetting() and direct Settings queries
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // home() / help() / legalNotice() dynamically select the current language column
        // (e.g. fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // home() fetches the footer article content
        $this->assertColumnsExist($pdo, 'Article', [
            'Id',
            'Title',
            'Content',
        ]);

        // home() fetches the carousel items
        $this->assertColumnsExist($pdo, 'Carousel', [
            'IdArticle',
            'Item',
        ]);

        // getSidebarMenuItems() fetches sidebar menu entries (filtered on What, ordered by Position)
        $this->assertColumnsExist($pdo, 'MenuItem', [
            'What',
            'Id',
            'ParentId',
            'Type',
            'Label',
            'Icon',
            'Url',
            'IdGroup',
            'ForMembers',
            'ForContacts',
            'ForAnonymous',
            'Position',
        ]);
    }
}
