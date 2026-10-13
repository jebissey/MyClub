<?php

declare(strict_types=1);

namespace tests\modules\PersonManager;

use tests\models\DataHelperTestCase;

class PersonControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Individual: get()/set() in edit(), editSave()
        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
            'FirstName',
            'LastName',
        ]);

        // Member: set() in activate()/delete(), get()/set() in edit(), editSave()
        $this->assertColumnsExist($pdo, 'Member', [
            'Id',
            'Inactivated',
            'Imported',
            'Alert',
            'MemberInfo',
            'CustomFields',
        ]);

        // Languages: help() dynamically selects the column of the current language
        // (fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // Settings: membershipSettingsEdit()/Save() and the custom fields definitions
        // via getSetting()/setSetting() (methods of Data that themselves call
        // get('Settings', ...) / set('Settings', ...))
        $this->assertColumnsExist($pdo, 'Settings', [
            'Name',
            'Value',
        ]);

        // NOTE: personDataHelper->create() and tableControllerDataHelper->
        // getActivePersonsQuery()/getDesactivatedPersonsQuery() delegate to
        // dedicated data helpers, to be covered separately in their own tests.
    }
}
