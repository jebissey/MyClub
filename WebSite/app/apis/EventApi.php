<?php

namespace app\apis;

use DateTime;
use Throwable;

use app\helpers\ApiEventDataHelper;
use app\helpers\ApiNeedDataHelper;
use app\helpers\ApiNeedTypeDataHelper;
use app\helpers\Application;
use app\helpers\AttributeDataHelper;
use app\helpers\Email;
use app\helpers\EventDataHelper;
use app\helpers\EventNeedHelper;
use app\helpers\MessageDataHelper;
use app\helpers\ParticipantDataHelper;
use app\helpers\PersonDataHelper;
use app\helpers\PersonPreferences;

class EventApi extends BaseApi
{
    private ApiNeedDataHelper $apiNeedDataHelper;
    private ApiNeedTypeDataHelper $apiNeedTypeDataHelper;
    private AttributeDataHelper $attributeDataHelper;
    private EventDataHelper $eventDataHelper;
    private EventNeedHelper $eventNeedHelper;
    private MessageDataHelper $messageDataHelper;
    private $personPreferences;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->apiNeedDataHelper = new ApiNeedDataHelper($application);
        $this->apiNeedTypeDataHelper = new ApiNeedTypeDataHelper($application);
        $this->attributeDataHelper = new AttributeDataHelper($application);
        $this->eventDataHelper = new EventDataHelper($application);
        $this->eventNeedHelper = new EventNeedHelper($application);
        $this->messageDataHelper = new MessageDataHelper($application);
        $this->personPreferences = new PersonPreferences($application);
    }

    #region Attribute
    public function createAttribute()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $json = file_get_contents('php://input');
            [$response, $statusCode] = $this->attributeDataHelper->insert(json_decode($json, true));
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function deleteAttribute($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            [$response, $statusCode] = $this->attributeDataHelper->delete_($id);
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getAttributes()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $this->renderPartial('app/views/eventType/attributes-list.latte', ['attributes' => $this->dataHelper->gets('Attribute')]);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getAttributesByEventType($eventTypeId)
    {
        if (!$eventTypeId) $this->renderJson(['success' => false, 'message' => 'Unknown event type'], 499);
        else $this->renderJson(['attributes' => $this->attributeDataHelper->getAttributesOf($eventTypeId)]);
    }

    public function updateAttribute()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            $json = file_get_contents('php://input');
            [$response, $statusCode] = $this->attributeDataHelper->update(json_decode($json, true));
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }
    #endregion

    #region Event
    public function deleteEvent($id): void
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isEventManager()) {
            [$response, $statusCode] = $this->eventDataHelper->delete_($id, $this->connectedUser->person->Id);
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function duplicateEvent($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isEventManager()) {
            [$response, $statusCode] = $this->eventDataHelper->duplicate($id, $this->connectedUser->person->Id);
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getEvent($id): void
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isEventManager()) {
            $this->renderJson([
                'success' => true,
                'event' => $this->eventDataHelper->getEvent($id),
                'attributes' => $this->eventDataHelper->getEventAttributes($id),
            ]);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function saveEvent(): void
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isEventManager()) {
            [$response, $statusCode] = (new ApiEventDataHelper($this->application))->update(json_decode(file_get_contents('php://input'), true), $this->connectedUser->person->Id);
            $this->renderJson($response, $statusCode);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function sendEmails()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isEventManager()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $eventId = $data['EventId'] ?? '';
                $event = $this->eventDataHelper->getEvent($eventId);
                if (!$event) {
                    $this->renderJson(['success' => false, 'message' => "Unknown event ($eventId)"], 403);
                    return;
                }
                $emailTitle = $data['Title'] ?? '';
                $recipients = $data['Recipients'] ?? '';
                $message = $data['Body'] ?? '';
                if ($recipients === 'registered') $participants = (new ParticipantDataHelper($this->application))->getEventParticipants($eventId);
                else if ($recipients === 'unregistered') {
                    //TODO
                } else if ($recipients === 'all') {
                    $participants = (new PersonDataHelper($this->application))->getInterestedPeople(
                        $this->eventDataHelper->getEventGroup($eventId),
                        $event->IdEventType ?? null,
                        (new DateTime($event->StartTime))->format('N') - 1,
                        $this->personPreferences->getPeriodOfDay($event->StartTime)
                    );
                } else {
                    $this->renderJson(['success' => false, 'message' => "Invalid recipients ($recipients)"], 404);
                    return;
                }
                if ($participants) {
                    $root = Application::$root;
                    $eventLink = $root . '/events/' . $event->Id;
                    $unsubscribeLink = $root . '/user/preferences';
                    $eventCreatorEmail = $this->dataHelper->get('Person', ['Id' => $event->CreatedBy])->Email;
                    if (!$eventCreatorEmail) {
                        $this->renderJson(['success' => false, 'message' => 'Invalid Email in file ' + __FILE__ + ' at line ' + __LINE__], 404);
                        return;
                    }
                    $bccList = $this->messageDataHelper->addWebAppMessages($eventId, $participants, $emailTitle . "\n\n" . $message);
                    $result = Email::send(
                        $eventCreatorEmail,
                        $eventCreatorEmail,
                        $emailTitle,
                        $message . "\n" . $eventLink . "\n\n Pour ne plus recevoir ce type de message vous pouvez mettre à jour vos préférences\n" . $unsubscribeLink,
                        null,
                        $bccList,
                        false
                    );
                    $this->renderJson(['success' => $result]);
                } else $this->renderJson(['success' => false, 'message' => 'No participant'], 404);
            } else $this->renderJson(['success' => false, 'message' =>  'Method ' + $_SERVER['REQUEST_METHOD'] + ' invalid in file ' + __FILE__ + ' at line ' + __LINE__]);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }
    #endregion

    #region Need
    public function deleteNeed($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                [$response, $statusCode] = $this->renderJson($this->apiNeedDataHelper->delete_($id));
                $this->renderJson($response, $statusCode);
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function saveNeed()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
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
                $needData = [
                    'Label' => $label,
                    'Name' => $name,
                    'ParticipantDependent' => $participantDependent,
                    'IdNeedType' => $idNeedType
                ];
                [$response, $statusCode] = $this->renderJson($this->apiNeedDataHelper->insertOrUpdate($id, $needData));
                $this->renderJson($response, $statusCode);
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function getEventNeeds($eventId)
    {
        $this->renderJson(['success' => true, 'needs' => $this->eventNeedHelper->needsForEvent($eventId)]);
    }

    public function getNeedsByNeedType($needTypeId)
    {
        $this->renderJson(['success' => true, 'needs' => $this->apiNeedTypeDataHelper->needsforNeedType($needTypeId)]);
    }
    #endregion

    #region NeedType
    public function deleteNeedType($id)
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                if (!$id) {
                    $this->renderJson(['success' => false, 'message' => 'Missing Id parameter'], 472);
                } else {
                    $countNeeds = $this->apiNeedDataHelper->countForNeedType($id);
                    if ($countNeeds > 0) {
                        $this->renderJson([
                            'success' => false,
                            'message' => 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé'
                        ], 409);
                    }
                }
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function saveNeedType()
    {
        $this->connectedUser = $this->connectedUser->get();
        if ($this->connectedUser->isWebmaster()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $name = $data['name'] ?? '';
                if (empty($name)) $this->renderJson(['success' => false, 'message' => "Missing parameter name ='$name'"], 472);
                else {
                    [$response, $statusCode] = $this->renderJson($this->apiNeedTypeDataHelper->insertOrUpdate($data['id'] ?? '', $name));
                    $this->renderJson($response, $statusCode);
                }
            } else $this->renderJson(['success' => false, 'message' => 'Bad request method'], 470);
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }
    #endregion

    #region Message
    public function addMessage()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['eventId']) || !isset($data['text'])) {
                $this->renderJson(['success' => false, 'message' => 'Données manquantes'], 400);
                return;
            }
            if (!$this->eventDataHelper->isUserRegistered($data['eventId'], $person->Email)) {
                $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
                return;
            }
            try {
                $messageId = $this->messageDataHelper->addMessage($data['eventId'], $person->Id, $data['text']);
                $messages = $this->messageDataHelper->getEventMessages($data['eventId']);
                $newMessage = null;

                foreach ($messages as $message) {
                    if ($message->Id == $messageId) {
                        $newMessage = $message;
                        break;
                    }
                }
                $this->renderJson(['success' => true, 'message' => 'Message ajouté', 'data' => $newMessage]);
            } catch (Throwable $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function updateMessage()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['messageId']) || !isset($data['text'])) {
                $this->renderJson(['success' => false, 'message' => 'Données manquantes'], 400);
                return;
            }
            try {
                $this->messageDataHelper->updateMessage($data['messageId'], $person->Id, $data['text']);

                $this->renderJson([
                    'success' => true,
                    'message' => 'Message mis à jour',
                    'data' => [
                        'messageId' => $data['messageId'],
                        'text' => $data['text']
                    ]
                ]);
            } catch (Throwable $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }

    public function deleteMessage()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['messageId'])) {
                $this->renderJson(['success' => false, 'message' => 'ID de message manquant'], 400);
                return;
            }

            try {
                $this->messageDataHelper->deleteMessage($data['messageId'], $person->Id);

                $this->renderJson([
                    'success' => true,
                    'message' => 'Message supprimé',
                    'data' => [
                        'messageId' => $data['messageId']
                    ]
                ]);
            } catch (Throwable $e) {
                $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
            }
        } else $this->renderJson(['success' => false, 'message' => 'User not allowed'], 403);
    }
    #endregion

    public function updateSupply(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJson(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        $userEmail = $this->connectedUser->get()->person->Email ?? '';
        if ($userEmail === '') {
            $this->renderJson(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $eventId = $input['eventId'] ?? null;
        $needId = $input['needId'] ?? null;
        $supply = intval($input['supply'] ?? 0);

        if (!$eventId || !$needId || $supply < 0) {
            $this->renderJson(['success' => false, 'message' => "Invalid parameters (eventId=eventId, needId=$needId, supply=$supply)"], 400);
            return;
        }

        if (!$this->eventDataHelper->isUserRegistered($eventId, $userEmail)) {
            $this->renderJson(['success' => false, 'message' => 'Non inscrit à cet événement'], 403);
            return;
        }

        $success = $this->eventDataHelper->updateUserSupply($eventId, $userEmail, $needId, $supply);
        if ($success) {
            $eventNeeds = $this->eventDataHelper->getEventNeeds($eventId);
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
        } else $this->renderJson(['success' => false, 'message' => 'Erreur lors de la mise à jour'], 500);
    }
}
