<?php

declare(strict_types=1);

namespace app\modules\Event;

use stdClass;
use app\enums\ApplicationError;
use app\enums\EventAudience;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\AttributeDataHelper;
use app\models\ChatMessageDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\EventParticipationDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\MessageDataHelper;
use app\models\MessageStatisticsDataHelper;
use app\models\ParticipantDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\MemberMapService;
use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\viewModels\ChatViewModel;
use app\modules\Event\viewModels\EventDetailViewModel;

final class EventDetailController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly AttributeDataHelper $attributeDataHelper,
        private readonly ChatMessageDataHelper $chatMessageDataHelper,
        private readonly EventDataHelper $eventDataHelper,
        private readonly EventNeedDataHelper $eventNeedDataHelper,
        private readonly EventParticipationDataHelper $eventParticipationDataHelper,
        private readonly MemberDirectoryDataHelper $memberDirectoryDataHelper,
        private readonly MemberMapService $memberMapService,
        private readonly MessageDataHelper $messageDataHelper,
        private readonly MessageStatisticsDataHelper $messageStatisticsDataHelper,
        private readonly ParticipantDataHelper $participantDataHelper,
    ) {
        parent::__construct($application);
    }

    public function show(int $eventId, string $message = '', string $messageType = ''): void
    {
        if (!$this->eventDataHelper->eventExists($eventId)) {
            $this->errors->badRequest("Event {$eventId} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser()->person ?? false;
        $rawToken = WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData()) ?? false;
        $token = is_string($rawToken) ? $rawToken : false;
        $event = $this->eventDataHelper->getEvent($eventId);

        if ($person === false && !$token && $event->audience !== EventAudience::ForAll) {
            $result = $this->application->getAuthenticationService()->handleRememberMeLogin($this->getRememberMeToken());
            if ($result && $result->isSuccess()) {
                $this->application->getConnectedUser()->get();
                $this->redirect(
                    To::str($_SERVER['REQUEST_URI']),
                    ApplicationError::Ok,
                    "Auto sign in succeeded for {$result->getUser()?->email}"
                );
                return;
            }
            $this->redirect('/user/sign/in?redirect=' . urlencode(To::str($_SERVER['REQUEST_URI'])));
        }

        $userEmail = $person->email ?? '';
        $participants = $this->participantDataHelper->getEventParticipants($eventId);
        $isRegistered = $this->eventParticipationDataHelper->isUserRegistered($eventId, $userEmail);

        // Carpooling map: only for registered users, restricted to the event participants
        $carpoolData = [];
        if ($isRegistered) {
            // Contacts (ContactId) have no location: only members (PersonId) are considered
            $memberIds = [];
            foreach ($participants as $participant) {
                if ($participant->personId !== null) {
                    $memberIds[] = $participant->personId;
                }
            }
            $carpoolData = $this->memberMapService->getLocationData(
                $this->memberDirectoryDataHelper->getLocatedMembers($memberIds)
            );
        }

        $viewModel = new EventDetailViewModel(
            eventId: $eventId,
            event: $event,
            attributes: array_map(
                static fn(stdClass $row): EventAttributeRow => new EventAttributeRow(
                    id: To::str($row->AttributeId),
                    name: To::str($row->Name),
                    detail: To::str($row->Detail),
                    color: To::str($row->Color),
                ),
                array_values($this->attributeDataHelper->getEventAttributes($eventId))
            ),
            participants: $participants,
            isRegistered: $isRegistered,
            countOfMessages: $this->messageStatisticsDataHelper->countUserMessagesForEvent($eventId),
            eventNeeds: $this->eventNeedDataHelper->getEventNeeds($eventId),
            participantSupplies: $this->eventNeedDataHelper->getParticipantSupplies($eventId),
            userSupplies: $this->eventNeedDataHelper->getUserSupplies($eventId, $userEmail),
            token: $token,
            carpoolData: $carpoolData,
            carpoolMaxZoom: 12,
            message: $message,
            messageType: $messageType,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Event/views/event_detail.latte', $viewModel);
    }

    public function showEventChat(int $eventId): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $eventChatRow = $this->eventDataHelper->findChatRow($eventId);
        if ($eventChatRow === null) {
            $this->errors->badRequest("Unknown event {$eventId}", __FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser(true)->person;
        $lastLogId = isset($_SESSION['last_log_id']) && is_int($_SESSION['last_log_id'])
            ? $_SESSION['last_log_id']
            : 0;

        $viewModel = new ChatViewModel(
            article: null,
            event: $eventChatRow,
            group: null,
            messages: $this->chatMessageDataHelper->getEventMessages($eventId),
            person: $person,
            navItems: [],
            newMessages: $this->messageDataHelper->hasNewMessages($person->id, $lastLogId),
            btnParent: "/event/{$eventId}",
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Common/views/chat.latte', $viewModel);
    }
}
