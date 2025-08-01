<?php

namespace app\helpers;

use RuntimeException;
use Throwable;

use app\enums\EventAudience;

class ApiEventDataHelper extends Data
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function update($data, $personId)
    {
        $values = [
            'Summary'         => $data['summary'] ?? '',
            'Description'     => $data['description'] ?? '',
            'Location'        => $data['location'] ?? '',
            'StartTime'       => $data['startTime'],
            'Duration'        => $data['duration'] ?? 1,
            'IdEventType'     => $data['idEventType'],
            'CreatedBy'       => $personId,
            'MaxParticipants' => $data['maxParticipants'] ?? 0,
            'Audience'        => $data['audience'] ?? EventAudience::ForClubMembersOnly->value,
            'LastUpdate'      => date('Y-m-d H:i:s'),
        ];

        $this->pdo->beginTransaction();
        try {
            if ($data['formMode'] == 'create') {
                $eventId = $this->set('Event', $values);
            } elseif ($data['formMode'] == 'update') {
                $this->set('Event', $values, ['Id' => $data['id']]);
                $eventId = $data['id'];

                $this->delete('EventAttribute', ['IdEvent' => $eventId]);
                $this->delete('EventNeed', ['IdEvent' => $eventId]);
            } else throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with formMode=" . $data['formMode']);
            $this->insertEventAttributes($eventId, $data['attributes'] ?? []);
            $this->insertEventNeeds($eventId, $data['needs'] ?? []);
            $this->pdo->commit();
            return [['success' => true, 'eventId' => $eventId], 200];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            return [[
                'success' => false,
                'message' => 'Erreur lors de l\'insertion en base de données',
                'error'   => $e->getMessage()
            ], 500];
        }
    }

    #region Private function
    private function insertEventAttributes(int $eventId, array $attributes): void
    {
        if (!empty($attributes)) {
            foreach ($attributes as $attributeId) {
                $this->set('EventAttribute', [
                    'IdEvent'     => $eventId,
                    'IdAttribute' => $attributeId
                ]);
            }
        }
    }

    private function insertEventNeeds(int $eventId, array $needs): void
    {
        if (!empty($needs)) {
            foreach ($needs as $need) {
                $this->set('EventNeed', [
                    'IdEvent' => $eventId,
                    'IdNeed'  => $need['id'],
                    'Counter' => $need['counter'],
                ]);
            }
        }
    }
}
