<?php

declare(strict_types=1);

namespace app\modules\Event;

use DateTime;
use Throwable;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\EventDataHelper;
use app\models\EventInvitationDataHelper;
use app\models\EventListDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\services\EmailService;
use app\modules\Common\valueObjects\EmailMessage;
use app\modules\Event\valueObjects\NewInvitation;
use app\modules\Event\viewModels\EventGuestViewModel;

final class EventGuestController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly EmailService $emailService,
        private readonly EventDataHelper $eventDataHelper,
        private readonly EventInvitationDataHelper $eventInvitationDataHelper,
        private readonly EventListDataHelper $eventListDataHelper,
    ) {
        parent::__construct($application);
    }

    public function guest(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $this->renderGuestPage();
    }

    public function guestInvite(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'email' => FilterInputRule::Email->value,
            'nickname' => FilterInputRule::PersonName->value,
            'eventId' => FilterInputRule::Int->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

        $email = To::str($input['email'] ?? '');
        if (empty($email)) {
            $this->renderGuestPage($this->translate('event.guest.error.invalid_email'), 'error');
            return;
        }

        $eventId = To::int($input['eventId'] ?? 0);
        if ($eventId <= 0) {
            $this->renderGuestPage($this->translate('event.guest.error.select_event'), 'error');
            return;
        }

        $event = $this->eventDataHelper->getEventExternal($eventId);
        if ($event === null) {
            $this->renderGuestPage($this->translate('event.guest.error.event_not_found'), 'error');
            return;
        }

        $nickname = To::str($input['nickname'] ?? '???');
        try {
            if ($this->eventInvitationDataHelper->findPendingInvitation($email, $eventId) !== null) {
                $this->renderGuestPage($this->translate('event.guest.error.already_invited'), 'error');
                return;
            }

            $token = bin2hex(random_bytes(32));
            $this->eventInvitationDataHelper->createInvitation(
                new NewInvitation(
                    email: $email,
                    nickname: $nickname,
                    eventId: $eventId,
                    token: $token,
                    invitedBy: $this->application->getConnectedUser()->person->id ?? 0,
                    invitedAt: (new DateTime())->format('Y-m-d H:i:s')
                ),
            );

            $invitationLink = Application::$root . "/event/$eventId?t=$token";
            $startTime = new DateTime($event->startTime);
            $greeting = $nickname !== ''
                ? $this->translate('event.guest.email.greeting_named', ['nickname' => $nickname])
                : $this->translate('event.guest.email.greeting');

            $body = $greeting . "\n\n"
                . $this->translate('event.guest.email.invited') . "\n\n"
                . $this->translate('event.guest.email.title') . " {$event->summary}\n"
                . $this->translate('event.guest.email.description') . " {$event->description}\n"
                . $this->translate('event.guest.email.location') . " {$event->location}\n"
                . $this->translate('event.guest.email.date', [
                    'date' => $startTime->format('d/m/Y'),
                    'time' => $startTime->format('H:i'),
                ]) . "\n\n"
                . $this->translate('event.guest.email.confirm') . "\n"
                . $invitationLink . "\n\n"
                . $this->translate('event.guest.email.regards') . "\n"
                . $this->translate('event.guest.email.team');

            $emailMessage = new EmailMessage(
                from: $this->application->getConnectedUser()->person->email ?? '',
                to: $email,
                subject: $this->translate('event.guest.email.subject', ['summary' => $event->summary]),
                body: $body,
                isHtml: false
            );

            if ($this->emailService->send($emailMessage)) {
                $this->renderGuestPage($this->translate('event.guest.success.sent', ['email' => $email]), 'success');
            } else {
                $this->renderGuestPage($this->translate('event.guest.error.send_failed', ['email' => $email]), 'error');
            }
        } catch (Throwable $e) {
            $this->renderGuestPage(
                $this->translate('event.guest.error.send_exception', ['message' => $e->getMessage()]),
                'error'
            );
        }
    }

    #region Private functions
    /**
     * Renders the guest page without any access check: callers must have checked access first.
     * Shared by the GET action and by the POST action, which redisplays the page with a message.
     */
    private function renderGuestPage(string $message = '', string $type = ''): void
    {
        $connectedUser = $this->application->getConnectedUser();

        $viewModel = new EventGuestViewModel(
            events: $this->eventListDataHelper->getEventsForAllOrGuest(),
            navbarTemplate: '../../Webmaster/views/navbar/eventManager.latte',
            layout: $this->getLayout('eventManager'),
            message: $message,
            messageType: $type,
            navItems: $this->getNavItems($connectedUser->person?->email),
            layoutParams: $this->getAllParams(['page' => $connectedUser->getPage(1)]),
        );

        $this->views->render('Event/views/guest.latte', $viewModel);
    }
    #endregion
}
