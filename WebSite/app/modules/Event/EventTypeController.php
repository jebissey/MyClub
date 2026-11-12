<?php

declare(strict_types=1);

namespace app\modules\Event;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\EventTypeDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;
use app\modules\Common\valueObjects\EventAttributeRow;
use app\modules\Common\valueObjects\IdNameRow;
use app\modules\Event\valueObjects\EventTypeNameGroupRow;
use app\modules\Event\viewModels\EventTypeEditViewModel;
use app\modules\Event\viewModels\EventTypesIndexViewModel;

final class EventTypeController extends TableController
{
    public function __construct(
        Application $application,
        private readonly EventTypeDataHelper $eventTypeDataHelper,
        private readonly TableControllerDataHelper $tableControllerDataHelper,
    ) {
        parent::__construct($application);
    }

    public function create(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $id = $this->dataHelper->set('EventType', ['Name' => '']);
        $this->redirect('/EventType/edit/' . $id);
    }

    public function delete(int $id): void
    {
        if (
            $this->userIsAllowedAndMethodIsGood(
                'GET',
                fn($u) => $u->isEventDesigner(),
                __FILE__,
                __LINE__
            ) && $this->eventTypeExists($id)
        ) {
            $this->dataHelper->set('EventType', ['Inactivated' => 1], ['Id' => $id]);
            $this->redirect('/eventTypes');
        }
    }

    public function edit(int $id): void
    {
        if (
            $this->userIsAllowedAndMethodIsGood(
                'GET',
                fn($u) => $u->isEventDesigner(),
                __FILE__,
                __LINE__
            ) && $this->eventTypeExists($id)
        ) {
            $eventTypeData = $this->dataHelper->get('EventType', ['Id' => $id], 'Name, IdGroup');
            if ($eventTypeData === false) {
                $this->errors->badRequest(
                    "Invalid EventType {$id} in file " . __FILE__ . ' at line ' . __LINE__,
                    __FILE__,
                    __LINE__
                );
                return;
            }
            $eventType = EventTypeNameGroupRow::fromStdClass($eventTypeData);
            $existingAttributes = $this->dataHelper->gets('EventTypeAttribute', ['IdEventType' => $id], 'IdAttribute');

            $viewModel = new EventTypeEditViewModel(
                name: $eventType->name,
                idGroup: $eventType->idGroup,
                groups: array_map(
                    static fn(object $row): IdNameRow => IdNameRow::fromStdClass($row),
                    array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'))
                ),
                attributes: array_map(
                    static fn(object $row): EventAttributeRow => EventAttributeRow::fromStdClass($row),
                    array_values($this->dataHelper->gets('Attribute', [], 'Id, Name, Detail, Color', 'Name'))
                ),
                existingAttributesIds: array_values(array_map(
                    static fn(object $a): int => To::int($a->IdAttribute),
                    $existingAttributes
                )),
                layoutParams: $this->getAllParams(['page' => $this->application->getConnectedUser()->getPage()]),
            );
            $this->views->render('Event/views/eventType_edit.latte', $viewModel);
        }
    }

    public function index(): void
    {
        if (!($this->application->getConnectedUser()->isEventDesigner())) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        $filterValues = [];
        $filterConfig = [];
        $columns = [
            ['field' => 'EventTypeName', 'label' => $this->translate('event.types.column.name')],
            ['field' => 'GroupName', 'label' => $this->translate('event.types.column.group')],
            ['field' => 'Attributes', 'label' => $this->translate('event.types.column.attributes')],
        ];
        $data = $this->prepareTableData($this->tableControllerDataHelper->getEventTypesQuery(), $filterValues);

        $viewModel = new EventTypesIndexViewModel(
            eventTypes: $data['items'],
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            extraParams: [],
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/eventTypes',
            deleteConfirm: $this->translate('event.types.delete_confirm'),
            i18n: [
                'nameRequired'  => $this->translate('event.types.js.name_required'),
                'errorPrefix'   => $this->translate('event.types.js.error_prefix'),
                'deleteConfirm' => $this->translate('event.types.js.delete_confirm'),
                'loadError'     => $this->translate('event.types.js.load_error'),
            ],
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );
        $this->views->render('Event/views/eventTypes_index.latte', $viewModel);
    }

    public function update(int $id): void
    {
        if (
            $this->userIsAllowedAndMethodIsGood(
                'POST',
                fn($u) => $u->isEventDesigner(),
                __FILE__,
                __LINE__
            ) && $this->eventTypeExists($id)
        ) {
            $schema = [
                'name' => FilterInputRule::HtmlSafeName->value,
                'idGroup' => FilterInputRule::Int->value,
                'attributes' => FilterInputRule::ArrayInt->value
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());

            $name = To::str($input['name'] ?? '???');

            $idGroup = isset($input['idGroup'])
                ? To::int($input['idGroup'])
                : null;

            $rawAttributes = $input['attributes'] ?? [];
            $attributes = is_array($rawAttributes)
                ? array_values(array_map(static fn($a): int => To::int($a), $rawAttributes))
                : [];

            $this->eventTypeDataHelper->update(
                $id,
                $name,
                $idGroup,
                $attributes
            );
            $this->redirect('/EventTypes');
        }
    }

    #region Private functions
    private function eventTypeExists(int $eventTypeId): bool
    {
        $eventType = $this->dataHelper->get('EventType', ['Id' => $eventTypeId], 'Id');
        if ($eventType === false) {
            $this->errors->badRequest(
                "Invalid EventType {$eventTypeId} in file " . __FILE__ . ' at line ' . __LINE__,
                __FILE__,
                __LINE__
            );
            return false;
        }
        return true;
    }
}
