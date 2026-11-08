<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\enums\FilterInputRule;
use app\enums\TimeOfDay;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\models\AlertRecipientDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Event\viewModels\CopyEmailsViewModel;
use app\modules\Event\viewModels\FetchEmailsViewModel;

final class EventEmailController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly AlertRecipientDataHelper $alertRecipientDataHelper,
        private readonly MemberDirectoryDataHelper $memberDirectoryDataHelper
    ) {
        parent::__construct($application);
    }

    public function copyEmails(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $schema = [
            'dayOfWeek' => FilterInputRule::Int->value,
            'timeOfDay' => FilterInputRule::HtmlSafeName->value,
            'idGroup' => FilterInputRule::Int->value,
            'idEventType' => FilterInputRule::Int->value,
        ];
        /** @var array{
         *     dayOfWeek?: int,
         *     timeOfDay?: string,
         *     idGroup?: int,
         *     idEventType?: int
         * } $input */
        $input = WebApp::filterInput(
            $schema,
            $this->flight->request()->data->getData()
        );

        $idGroup = $input['idGroup'] ?? null;
        $idEventType = $input['idEventType'] ?? null;
        $dayOfWeek = $input['dayOfWeek'] ?? null;
        $timeOfDay = $input['timeOfDay'] ?? '';
        $filteredEmails = $this->alertRecipientDataHelper->getEmailsOfInterestedPeople($idGroup, $idEventType, $dayOfWeek, $timeOfDay);
        $groupName = $idGroup !== null && $idGroup > 0
            ? $this->dataHelper->getString('Group', ['Id' => $idGroup], 'Name')
            : '';
        $eventTypeName = $idEventType !== null && $idEventType > 0
            ? $this->dataHelper->getString('EventType', ['Id' => $idEventType], 'Name')
            : '';
        $dayOfWeekName = $dayOfWeek !== null ? TranslationManager::getWeekdayNames()[$dayOfWeek] : '';
        $translatedTimeOfDay = ($this->t)($timeOfDay);

        $viewModel = new CopyEmailsViewModel(
            emailsJson: json_encode($filteredEmails, JSON_THROW_ON_ERROR),
            emails: array_values($filteredEmails),
            filters: "{$groupName} / {$eventTypeName} / {$dayOfWeekName} / {$translatedTimeOfDay}",
            people: $this->memberDirectoryDataHelper->getActiveMembersContactInfoByEmail(),
            i18n: [
                'copySuccess' => ($this->t)('event.copy_emails.clipboard.success'),
                'copyError'   => ($this->t)('event.copy_emails.clipboard.error'),
            ],
            layoutParams: $this->getAllParams(['page' => $this->application->getConnectedUser()->getPage()]),
        );
        $this->views->render('Event/views/copyToClipBoard.latte', $viewModel);
    }

    public function fetchEmails(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventManager(), __FILE__, __LINE__)) {
            return;
        }

        $viewModel = new FetchEmailsViewModel(
            groups: array_map(
                static fn(object $row): IdNameRow => IdNameRow::fromStdClass($row),
                array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'))
            ),
            eventTypes: array_map(
                static fn(object $row): IdNameRow => IdNameRow::fromStdClass($row),
                array_values($this->dataHelper->gets('EventType', ['Inactivated' => 0], 'Id, Name', 'Name'))
            ),
            weekdayNames: TranslationManager::getWeekdayNames(),
            timeOptions: $this->getAllLabels(),
            layoutParams: $this->getAllParams(['page' => $this->application->getConnectedUser()->getPage()]),
        );
        $this->views->render('Event/views/getEmails.latte', $viewModel);
    }

    public function help(): void
    {
        $this->renderHelp('Help_Event_Email', fn($u) => $u->isEventManager(), __FILE__, __LINE__);
    }


#region Privates functions
    /**
     * @return list<array{
     *     value:string,
     *     label:string
     * }>
     */
    private function getAllLabels(): array
    {
        return array_map(
            fn(TimeOfDay $case) => [
                'value' => $case->value,
                'label' => ($this->t)($case->value)
            ],
            TimeOfDay::cases()
        );
    }
}
