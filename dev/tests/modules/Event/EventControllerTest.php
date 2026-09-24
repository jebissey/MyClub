<?php

declare(strict_types=1);

namespace tests\modules\Event;

use tests\models\DataHelperTestCase;

class EventControllerTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        // Languages : help() sélectionne dynamiquement la colonne de langue
        // courante (fr_FR, en_US, pl_PL) via $this->dataHelper->get('Languages', ..., $lang)
        // dans nextEventsHelp() et help()
        $this->assertColumnsExist($pdo, 'Languages', [
            'Name',
            'fr_FR',
            'en_US',
            'pl_PL',
        ]);

        // EventType : gets() dans nextEvents(), weekEvents()
        $this->assertColumnsExist($pdo, 'EventType', [
            'Id',
            'Name',
            'Inactivated',
            'IdGroup',
        ]);

        // NeedType : gets('NeedType', [], 'Id, Name') dans nextEvents()
        $this->assertColumnsExist($pdo, 'NeedType', [
            'Id',
            'Name',
        ]);

        // Attribute : gets() dans nextEvents(), weekEvents()
        $this->assertColumnsExist($pdo, 'Attribute', [
            'Id',
            'Name',
            'Detail',
            'Color',
        ]);

        // Event : get() dans show(), register(), showEventChat()
        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
            'Audience',
            'CreatedBy',
            'Summary',
            'StartTime',
            'Duration',
            'Location',
        ]);

        // Message : gets('Message', ['"From"' => 'User', 'EventId' => ...], '*') dans show()
        $this->assertColumnsExist($pdo, 'Message', [
            'From',
            'EventId',
        ]);

        // NOTE : toute la logique d'inscription (Individual/Contact/Invitation/
        // Participant) est déléguée à eventDataHelper depuis la refonte du
        // flux d'invitation (V83ToV84Migrator) — voir EventDataHelperTest.
    }
}