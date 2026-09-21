<?php

declare(strict_types=1);

namespace tests\modules\PersonManager;

use tests\models\DataHelperTestCase;

class PersonControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Individual : get()/set() dans edit(), editSave()
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
        ]);

        // Member : set() dans activate()/delete(), get()/set() dans edit(), editSave()
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
            'Imported',
            'Alert',
            'MemberInfo',
        ]);

        // Languages : help() sélectionne dynamiquement la colonne de langue
        // courante (fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // Settings : membershipSettingsEdit()/Save() via getSetting()/setSetting()
        // (méthodes de Data qui font elles-mêmes get('Settings', ...) / set('Settings', ...))
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // NOTE : personDataHelper->create() et tableControllerDataHelper->
        // getActivePersonsQuery()/getDesactivatedPersonsQuery() délèguent à des
        // data helpers dédiés, à couvrir séparément dans leurs propres tests.
    }
}