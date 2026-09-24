<?php

declare(strict_types=1);

namespace tests\modules\PersonManager;

use tests\models\DataHelperTestCase;

class GroupControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Authorization : gets('Authorization', ['Id <> 1' => null], '*', 'Name')
        // dans groupCreate(), groupCreateSave(), groupEdit(), groupEditSave()
        $this->assertColumnsExist($pdo, 'Authorization', [
            'Id',
            'Name',
        ]);

        // Group : get('Group', ..., 'Name, SelfRegistration') dans groupEdit(),
        // groupEditSave() ; get('Group', ..., 'Id, Name') dans showGroupChat()
        $this->assertColumnsExist($pdo, 'Group', [
            'Id',
            'Name',
            'SelfRegistration',
        ]);

        // GroupAuthorization : gets('GroupAuthorization', ..., 'IdAuthorization')
        // dans groupEdit(), groupEditSave()
        $this->assertColumnsExist($pdo, 'GroupAuthorization', [
            'IdGroup',
            'IdAuthorization',
        ]);

        // NOTE : groupDataHelper (insert/inactive/update/getGroupsWithAuthorizations),
        // personGroupDataHelper->isPersonInGroup() et messageDataHelper
        // (getGroupMessages/hasNewMessages) délèguent à des data helpers dédiés,
        // à couvrir séparément dans leurs propres tests.
    }
}