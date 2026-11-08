<?php

declare(strict_types=1);

namespace tests\models;

class EventParticipationDataHelperTest extends DataHelperTestCase
{
    public function testQueriedColumnsExistInDatabaseSchema(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();

        $this->assertColumnsExist($pdo, 'Participant', [
            'Id',
            'IdEvent',
            'IdIndividual',
            'InvitedBy',
            'InvitedAt',
        ]);

        $this->assertColumnsExist($pdo, 'ParticipantSupply', [
            'Id',
            'IdParticipant',
        ]);

        $this->assertColumnsExist($pdo, 'Individual', [
            'Id',
            'Email',
        ]);

        $this->assertColumnsExist($pdo, 'Event', [
            'Id',
        ]);
    }
}