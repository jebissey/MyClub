<?php

namespace app\api;

use Flight\Engine;
use PDO;
use DateTime;
use Exception;
use app\controllers\BaseController;
use app\helpers\Email;
use app\helpers\Event;
use app\helpers\EventAudience;
use app\helpers\Message;

class EventApi extends BaseController
{
    private $event;
    private $email;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->event = new Event($this->pdo);
        $this->email = new Email($this->pdo);
    }

    #region Attribute
    public function createAttribute()
    {
        if ($this->getPerson(['Webmaster'])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            try {
                $this->pdo->beginTransaction();
                $this->fluent->insertInto('Attribute', [
                    'Name'   => $data['name'],
                    'Detail' => $data['detail'],
                    'Color'  => $data['color']
                ])->execute();
                $this->pdo->commit();
                $this->renderJson(['success' => true]);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function deleteAttribute($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            try {
                $this->pdo->beginTransaction();
                $this->fluent->deleteFrom('EventTypeAttribute')
                    ->where('IdAttribute', $id)
                    ->execute();
                $this->fluent->deleteFrom('Attribute')
                    ->where('Id', $id)
                    ->execute();

                $this->pdo->commit();
                $this->renderJson(['success' => true]);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function getAttributes()
    {
        if ($this->getPerson(['Webmaster'])) {
            $attributes = $this->fluent->from('Attribute')
                ->orderBy('Name')
                ->fetchAll();

            $this->render('app/views/eventType/attributes-list.latte', $this->params->getAll([
                'attributes' => $attributes
            ]));
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function getAttributesByEventType($eventTypeId)
    {
        if (!$eventTypeId) {
            $this->renderJson(['success' => false, 'message' => 'Unknown event type'], 499);
        } else {
            $query = $this->fluent->from('EventTypeAttribute')
                ->select('Attribute.*')
                ->join('Attribute ON EventTypeAttribute.IdAttribute = Attribute.Id')
                ->where('EventTypeAttribute.IdEventType', $eventTypeId);
            $this->renderJson(['attributes' => $query->fetchAll()]);
        }
    }

    public function updateAttribute()
    {
        if ($this->getPerson(['Webmaster'])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            try {
                $this->pdo->beginTransaction();
                $this->fluent->update('Attribute')
                    ->set([
                        'Name'   => $data['name'],
                        'Detail' => $data['detail'],
                        'Color'  => $data['color']
                    ])
                    ->where('Id', $data['id'])
                    ->execute();
                $this->pdo->commit();
                $this->renderJson(['success' => true]);
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }
    #endregion

    #region Event
    public function deleteEvent($id): void
    {
        if ($person = $this->getPerson(['EventManager'])) {
            if (!$this->fluent->from('Event')->where('Id', $id)->where('CreatedBy', $person->Id)->fetch()) {
                $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
                return;
            }
            try {
                $this->pdo->beginTransaction();

                $this->fluent->deleteFrom('EventAttribute')->where('IdEvent', $id)->execute();
                // TODO manage participant and paticipantSupply
                $this->fluent->deleteFrom('Event')->where('Id', $id)->execute();
                $this->pdo->commit();
                $this->renderJson(['success' => true]);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson([
                    'success' => false,
                    'message' => 'Erreur lors de la suppression en base de données',
                    'error' => $e->getMessage()
                ], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function duplicateEvent($id)
    {
        if ($person = $this->getPerson(['EventManager'])) {
            try {
                $this->pdo->beginTransaction();

                $event = $this->fluent->from('Event')->where('Id', $id)->fetch();
                if (!$event) {
                    $this->pdo->rollBack();
                    $this->renderJson(['success' => false, 'message' => 'Unknow event'], 471);
                    return;
                }

                $newEvent = [
                    'Summary' => $event->Summary,
                    'Description' => $event->Description,
                    'Location' => $event->Location,
                    'StartTime' => (new DateTime('today 23:59'))->format('Y-m-d H:i:s'),
                    'Duration' => $event->Duration,
                    'IdEventType' => $event->IdEventType,
                    'CreatedBy' => $person->Id,
                    'MaxParticipants' => $event->MaxParticipants,
                    'Audience' => $event->Audience
                ];
                $newEventId = $this->fluent->insertInto('Event')->values($newEvent)->execute();

                $attributes = $this->fluent->from('EventAttribute')->where('IdEvent', $id)->fetchAll();
                foreach ($attributes as $attr) {
                    $this->fluent->insertInto('EventAttribute')->values([
                        'IdEvent' => $newEventId,
                        'IdAttribute' => $attr->IdAttribute,
                    ])->execute();
                }
                $this->pdo->commit();
                $this->renderJson(['success' => true, 'newEventId' => $newEventId]);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function getEvent($id): void
    {
        if ($this->getPerson(['EventManager'])) {
            $this->renderJson([
                'success' => true,
                'event' => $this->fluent->from('Event')->where('Id', $id)->fetch(),
                'attributes' => $this->fluent->from('EventAttribute')
                    ->join('Attribute ON EventAttribute.IdAttribute = Attribute.Id')
                    ->select('Attribute.Name AS Name, Attribute.Detail AS Detail, Attribute.Color AS Color, Attribute.Id AS AttributeId')
                    ->where('IdEvent', $id)->fetchall(),
            ]);
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function saveEvent(): void
    {
        if ($person = $this->getPerson(['EventManager'])) {
            $data = json_decode(file_get_contents('php://input'), true);
            $values = [
                'Summary'        => $data['summary'] ?? '',
                'Description'    => $data['description'] ?? '',
                'Location'       => $data['location'] ?? '',
                'StartTime'      => $data['startTime'],
                'Duration'       => $data['duration'] ?? 1,
                'IdEventType'    => $data['idEventType'],
                'CreatedBy'      => $person->Id,
                'MaxParticipants' => $data['maxParticipants'] ?? 0,
                'Audience'       => $data['audience'] ?? EventAudience::ForClubMembersOnly->value,
            ];

            $this->pdo->beginTransaction();
            try {
                if ($data['formMode'] == 'create') {
                    $eventId = $this->fluent->insertInto('Event')->values($values)->execute();
                } elseif ($data['formMode'] == 'update') {
                    $this->fluent->update('Event')->set($values)->where('Id', $data['id'])->execute();
                    $eventId = $data['id'];

                    $this->fluent->deleteFrom('EventAttribute')->where('IdEvent', $eventId)->execute();
                    $this->fluent->deleteFrom('EventNeed')->where('IdEvent', $eventId)->execute();
                } else {
                    die('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__ . " with formMode=" . $data['formMode']);
                }
                $this->insertEventAttributes($eventId, $data['attributes'] ?? []);
                $this->insertEventNeeds($eventId, $data['needs'] ?? []);
                $this->pdo->commit();
                $this->renderJson(['success' => true, 'eventId' => $eventId]);
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $this->renderJson([
                    'success' => false,
                    'message' => 'Erreur lors de l\'insertion en base de données',
                    'error'   => $e->getMessage()
                ], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }
    private function insertEventAttributes(int $eventId, array $attributes): void
    {
        if (!empty($attributes)) {
            foreach ($attributes as $attributeId) {
                $this->fluent->insertInto('EventAttribute')
                    ->values([
                        'IdEvent'    => $eventId,
                        'IdAttribute' => $attributeId
                    ])
                    ->execute();
            }
        }
    }
    private function insertEventNeeds(int $eventId, array $needs): void
    {
        if (!empty($needs)) {
            foreach ($needs as $need) {
                $this->fluent->insertInto('EventNeed')
                    ->values([
                        'IdEvent' => $eventId,
                        'IdNeed'  => $need['id'],
                        'Counter' => $need['counter'],
                    ])
                    ->execute();
            }
        }
    }

    public function sendEmails()
    {
        if ($this->getPerson(['EventManager'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $eventId = $data['EventId'] ?? '';
                $event = $this->fluent->from('Event')->where('Id', $eventId)->fetch();
                if (!$event) {
                    $this->renderJson(['success' => false, 'message' => "Unknown event ($eventId)"], 403);
                    return;
                }
                $emailTitle = $data['Title'] ?? '';
                $recipients = $data['Recipients'] ?? '';
                $message = $data['Body'] ?? '';
                if ($recipients === 'registered') {
                    $participants = $this->event->getEventParticipants($eventId);
                } else if ($recipients === 'unregistered') {
                } else if ($recipients === 'all') {
                    $participants = $this->email->getInterestedPeople(
                        $this->event->getEventGroup($eventId),
                        ($this->fluent->from('Event')->where('Id', $eventId)->fetch())->IdEventType ?? null,
                        (new DateTime($event->StartTime))->format('N') - 1,
                        $this->getPeriodOfDay($event->StartTime)
                    );
                } else {
                    $this->renderJson(['success' => false, 'message' => "Invalid recipients ($recipients)"], 404);
                    return;
                }
                if ($participants) {
                    $eventLink = 'https://' . $_SERVER['HTTP_HOST'] . '/events/' . $event->Id;
                    $sentError = 0;
                    $sent = 0;
                    foreach ($participants as $participant) {
                        $sent++;
                        $this->fluent->insertInto('Message')
                            ->values([
                                'EventId' => $eventId,
                                'PersonId' => $participant->Id,
                                'Text' => $emailTitle,
                                '"From"' => 'Webapp'
                            ])
                            ->execute();
                        if (!mail($participant->Email, $emailTitle, $message . $eventLink)) {
                            $sentError++;
                        }
                    }
                } else {
                    $this->renderJson(['success' => false, 'message' => 'No participant'], 404);
                    return;
                }
                $this->renderJson(['success' => true, 'message' => "sent = $sent ; sentError = $sentError"]);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }
    #endregion

    #region Need
    public function deleteNeed($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (!$id) {
                    $this->renderJson(['success' => false, 'message' => 'Missing ID parameter'], 472);
                } else {
                    try {
                        $this->fluent->deleteFrom('Need')->where('Id', $id)->execute();
                        $this->renderJson(['success' => true]);
                    } catch (\Exception $e) {
                        $this->renderJson(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
                    }
                }
            } else {
                $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function saveNeed()
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? false;
                $label = $data['label'] ?? '';
                $name = $data['name'] ?? '';
                $participantDependent = isset($data['participantDependent']) ? intval($data['participantDependent']) : 0;
                $idNeedType = $data['idNeedType'] ?? null;
                if (empty($label)) {
                    $this->renderJson(['success' => false, 'message' => 'Missing parameter label'], 472);
                    return;
                }
                if (empty($name)) {
                    $this->renderJson(['success' => false, 'message' => 'Missing parameter name'], 472);
                    return;
                }
                if (!$idNeedType) {
                    $this->renderJson(['success' => false, 'message' => 'Missing parameter idNeedType'], 472);
                    return;
                }
                try {
                    $needData = [
                        'Label' => $label,
                        'Name' => $name,
                        'ParticipantDependent' => $participantDependent,
                        'IdNeedType' => $idNeedType
                    ];
                    if ($id) {
                        $this->fluent->update('Need')->set($needData)->where('Id', $id)->execute();
                    } else {
                        $id = $this->fluent->insertInto('Need')->values($needData)->execute();
                    }
                    $this->renderJson(['success' => true, 'id' => $id]);
                } catch (\Exception $e) {
                    $this->renderJson(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
                }
            } else {
                $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function getEventNeeds($eventId)
    {
        $needs = $this->fluent->from('EventNeed')
            ->select('EventNeed.*, Need.Label, Need.Name, Need.ParticipantDependent, NeedType.Name as TypeName')
            ->join('Need ON EventNeed.IdNeed = Need.Id')
            ->join('NeedType ON Need.IdNeedType = NeedType.Id')
            ->where('EventNeed.IdEvent', $eventId)
            ->fetchAll();
        $this->renderJson(['success' => true, 'needs' => $needs]);
    }

    public function getNeedsByNeedType($needTypeId)
    {
        $needs = $this->fluent->from('Need')
            ->select('Need.*, NeedType.Name as TypeName')
            ->join('NeedType ON Need.IdNeedType = NeedType.Id')
            ->where('Need.IdNeedType', $needTypeId)
            ->fetchAll();
        $this->renderJson(['success' => true, 'needs' => $needs]);
    }
    #endregion

    #region NeedType
    public function deleteNeedType($id)
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (!$id) {
                    $this->renderJson(['success' => false, 'message' => 'Missing Id parameter'], 472);
                } else {
                    $countNeeds = $this->fluent->from('Need')->where('IdNeedType', $id)->count();
                    if ($countNeeds > 0) {
                        $this->renderJson([
                            'success' => false,
                            'message' => 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé'
                        ], 409);
                    } else {
                        try {
                            $this->fluent->deleteFrom('NeedType')->where('Id', $id)->execute();
                            $this->renderJson(['success' => true]);
                        } catch (\Exception $e) {
                            $this->renderJson(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
                        }
                    }
                }
            } else {
                $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function saveNeedType()
    {
        if ($this->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $data['id'] ?? '';
                $name = $data['name'] ?? '';
                if (empty($name)) {
                    $this->renderJson(['success' => false, 'message' => "Missing parameter name ='$name'"], 472);
                } else {
                    try {
                        if ($id) {
                            $this->fluent->update('NeedType')->set(['Name' => $name])->where('Id', $id)->execute();
                        } else {
                            $id = $this->fluent->insertInto('NeedType')->values(['Name' => $name])->execute();
                        }
                        $this->renderJson(['success' => true, 'id' => $id]);
                    } catch (\Exception $e) {
                        $this->flight->json(['success' => 'false', 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()]);
                    }
                }
            } else {
                $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }
    #endregion

    #region Message
    public function addMessage()
    {
        if ($person = $this->getPerson([])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['eventId']) || !isset($data['text'])) {
                $this->renderJson(['success' => false, 'message' => 'Données manquantes'], 400);
                return;
            }

            $eventHelper = new Event($this->pdo);
            if (!$eventHelper->isUserRegistered($data['eventId'], $person->Email)) {
                $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
                return;
            }

            try {
                $messageHelper = new Message($this->pdo);
                $messageId = $messageHelper->addMessage($data['eventId'], $person->Id, $data['text']);

                $messages = $messageHelper->getEventMessages($data['eventId']);
                $newMessage = null;

                foreach ($messages as $message) {
                    if ($message->Id == $messageId) {
                        $newMessage = $message;
                        break;
                    }
                }
                $this->renderJson(['success' => true, 'message' => 'Message ajouté', 'data' => $newMessage]);
            } catch (\Exception $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function updateMessage()
    {
        if ($person = $this->getPerson([])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['messageId']) || !isset($data['text'])) {
                $this->renderJson(['success' => false, 'message' => 'Données manquantes'], 400);
                return;
            }

            try {
                $messageHelper = new Message($this->pdo);
                $messageHelper->updateMessage($data['messageId'], $person->Id, $data['text']);

                $this->renderJson([
                    'success' => true,
                    'message' => 'Message mis à jour',
                    'data' => [
                        'messageId' => $data['messageId'],
                        'text' => $data['text']
                    ]
                ]);
            } catch (\Exception $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }

    public function deleteMessage()
    {
        if ($person = $this->getPerson([])) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['messageId'])) {
                $this->renderJson(['success' => false, 'message' => 'ID de message manquant'], 400);
                return;
            }

            try {
                $messageHelper = new Message($this->pdo);
                $messageHelper->deleteMessage($data['messageId'], $person->Id);

                $this->renderJson([
                    'success' => true,
                    'message' => 'Message supprimé',
                    'data' => [
                        'messageId' => $data['messageId']
                    ]
                ]);
            } catch (\Exception $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else {
            $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
        }
    }
    #endregion


    public function updateSupply(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->renderJson(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        $person = $this->getPerson();
        $userEmail = $person->Email ?? '';
        if ($userEmail === '') {
            http_response_code(401);
            $this->renderJson(['success' => false, 'message' => 'Non authentifié']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $eventId = $input['eventId'] ?? null;
        $needId = $input['needId'] ?? null;
        $supply = intval($input['supply'] ?? 0);

        if (!$eventId || !$needId || $supply < 0) {
            http_response_code(400);
            $this->renderJson(['success' => false, 'message' => "Invalid parameters (eventId=eventId, needId=$needId, supply=$supply)"]);
            return;
        }

        $event = new Event($this->pdo);
        if (!$event->isUserRegistered($eventId, $userEmail)) {
            http_response_code(403);
            $this->renderJson(['success' => false, 'message' => 'Non inscrit à cet événement']);
            return;
        }

        $success = $event->updateUserSupply($eventId, $userEmail, $needId, $supply);
        if ($success) {
            $eventNeeds = $event->getEventNeeds($eventId);
            $updatedNeed = null;

            foreach ($eventNeeds as $need) {
                if ($need->Id == $needId) {
                    $updatedNeed = [
                        'id' => $need->Id,
                        'providedQuantity' => $need->ProvidedQuantity,
                        'requiredQuantity' => $need->RequiredQuantity,
                        'percentage' => $need->RequiredQuantity > 0 ? min(100, ($need->ProvidedQuantity / $need->RequiredQuantity) * 100) : 0
                    ];
                    break;
                }
            }

            $this->renderJson([
                'success' => true,
                'message' => 'Apport mis à jour avec succès',
                'updatedNeed' => $updatedNeed
            ]);
        } else {
            $this->renderJson(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
        }
    }



    private function getPeriodOfDay($dateString)
    {
        $date = new DateTime($dateString);
        $hour = (int)$date->format('H');

        if ($hour < 13) {
            return 'morning';
        } elseif ($hour < 18) {
            return 'afternoon';
        } else {
            return 'evening';
        }
    }
}
