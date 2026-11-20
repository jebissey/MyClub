<?php

declare(strict_types=1);

namespace app\modules\Event;

use Throwable;
use app\enums\ApplicationError;
use app\enums\ContactRegistrationOutcome;
use app\enums\EventAudience;
use app\enums\FilterInputRule;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\EventDataHelper;
use app\models\EventParticipationDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Event\services\ExternalContactRegistrationService;
use app\modules\Event\viewModels\RegistrationSuccessViewModel;

final class EventRegistrationController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly EventDataHelper $eventDataHelper,
        private readonly EventDetailController $eventDetailController,
        private readonly EventParticipationDataHelper $eventParticipationDataHelper,
        private readonly ExternalContactRegistrationService $contactRegistration,
    ) {
        parent::__construct($application);
    }

    public function registerSet(int $eventId, ?string $token = null): void
    {
        $this->register($eventId, true, $token);
    }

    public function registerUnset(int $eventId, ?string $token = null): void
    {
        $this->register($eventId, false, $token);
    }

    private function register(int $eventId, bool $set, ?string $token = null): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->eventDataHelper->eventExists($eventId)) {
            $this->errors->badRequest("Event ({$eventId}) doesn't exist", __FILE__, __LINE__);
            return;
        }
        try {
            if ($token === null) {
                $rawToken = WebApp::getFiltered('t', FilterInputRule::Token->value, $this->flight->request()->query->getData());
                $token = is_string($rawToken) ? $rawToken : null;
            }
            $person = $this->application->getConnectedUser()->person;
            if ($person !== null) {
                // Logged-in member: direct (un)registration, without going through an Invitation.
                $userId = $person->id;
                if ($set) {
                    if ($eventId > 0 && !$this->eventParticipationDataHelper->isAlreadyRegistered($userId, $eventId)) {
                        $this->eventParticipationDataHelper->addConfirmedParticipant($userId, $eventId);
                    }
                } else {
                    $this->eventParticipationDataHelper->removeParticipantByIndividual($userId, $eventId);
                }
            } elseif ($token !== null) {
                // External contact using an invitation link.
                $result = $this->contactRegistration->handle($eventId, $token, $set);

                if ($result->outcome->isError()) {
                    $this->eventDetailController->show($eventId, $this->translate($result->outcome->translationKey()), 'error');
                    return;
                }

                if ($result->outcome === ContactRegistrationOutcome::Registered) {
                    $event = $this->eventDataHelper->findRegistrationInfo($eventId);
                    if ($event === null) {
                        $this->eventDetailController->show($eventId, $this->translate('event.register.error.unknown_event'), 'error');
                        return;
                    }

                    $viewModel = new RegistrationSuccessViewModel(
                        event: $event,
                        contactId: (int) $result->contactId,
                        contactEmail: (string) $result->contactEmail,
                        contactNickName: $result->contactNickName,
                        navItems: $this->getNavItems($this->application->getConnectedUser(true)->person->email),
                        layoutParams: $this->getAllParams([
                            'page' => $this->application->getConnectedUser()->getPage(),
                        ]),
                    );
                    $this->views->render('Common/views/registration_success.latte', $viewModel);

                    return;
                }
                // Unregistered: fall through to the final redirect
            } else {
                $audience = $this->eventDataHelper->findAudience($eventId);
                if ($audience !== null && $audience->audience === EventAudience::ForAll) {
                    $this->redirect('/contact/event/' . $eventId);
                } else {
                    $this->errors->forbidden(__FILE__, __LINE__);
                }
                return;
            }
            $this->redirect('/event/' . $eventId);
        } catch (QueryException $e) {
            $this->redirect('/', ApplicationError::BadRequest, $e->getMessage());
        } catch (Throwable $e) {
            $this->redirect('/', ApplicationError::Error, $e->getMessage());
        }
    }
}
