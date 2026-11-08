<?php

declare(strict_types=1);

namespace app\modules\Event;

use stdClass;
use app\helpers\Application;
use app\models\NeedDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Event\valueObjects\NeedTypeRow;
use app\modules\Event\viewModels\EventNeedsViewModel;

final class EventNeedController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly NeedDataHelper $needDataHelper,
    ) {
        parent::__construct($application);
    }

    public function needs(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!$connectedUser->isEventDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }

        $viewModel = new EventNeedsViewModel(
            navItems: $this->getNavItems($connectedUser->person?->email),
            needTypes: array_map(
                static fn(stdClass $row): NeedTypeRow => NeedTypeRow::fromStdClass($row),
                array_values($this->dataHelper->gets('NeedType', [], 'Id, Name', 'Name'))
            ),
            needs: $this->needDataHelper->getNeedsAndTheirTypes(),
            i18n: [
                'addTypeTitle'      => $this->translate('event.needs.modal.type.add'),
                'editTypeTitle'     => $this->translate('event.needs.modal.type.edit'),
                'addNeedTitle'      => $this->translate('event.needs.modal.need.add'),
                'editNeedTitle'     => $this->translate('event.needs.modal.need.edit'),
                'deleteTypeConfirm' => $this->translate('event.needs.delete.type_confirm'),
                'deleteNeedConfirm' => $this->translate('event.needs.delete.need_confirm'),
                'errorPrefix'       => $this->translate('event.needs.js.error_prefix'),
                'genericError'      => $this->translate('event.needs.js.generic_error'),
                'saveError'         => $this->translate('event.needs.js.save_error'),
                'deleteError'       => $this->translate('event.needs.js.delete_error'),
            ],
            layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
        );

        $this->views->render('Event/views/event_needs.latte', $viewModel);
    }
}
