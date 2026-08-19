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
use app\valueObjects\EventTypeNameGroupRow;

class EventTypeController extends TableController
{
    public function __construct(
        Application $application,
        private EventTypeDataHelper $eventTypeDataHelper,
        private TableControllerDataHelper $tableControllerDataHelper,
    ) {
        parent::__construct($application);
    }

    public function create(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
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
                $this->raiseBadRequest(
                    "Invalid EventType {$id} in file " . __FILE__ . ' at line ' . __LINE__,
                    __FILE__,
                    __LINE__
                );
                return;
            }
            /** @var object{Name: string, IdGroup: int|string|null} $eventTypeData */
            $eventType = EventTypeNameGroupRow::fromStdClass($eventTypeData);
            $existingAttributes = $this->dataHelper->gets('EventTypeAttribute', ['IdEventType' => $id], 'IdAttribute');

            $this->render('Event/views/eventType_edit.latte', $this->getAllParams([
                'name' => $eventType->Name,
                'idGroup' => $eventType->IdGroup,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'attributes' => $this->dataHelper->gets('Attribute', [], '*', 'Name'),
                'existingAttributesIds' => array_map(fn($a) => $a->IdAttribute, $existingAttributes),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function index(): void
    {
        if (!($this->application->getConnectedUser()->isEventDesigner())) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        $filterValues = [];
        $filterConfig = [];
        $columns = [
            ['field' => 'EventTypeName', 'label' => 'Nom'],
            ['field' => 'GroupName', 'label' => 'Groupe'],
            ['field' => 'Attributes', 'label' => 'Attributs'],
        ];
        $data = $this->prepareTableData($this->tableControllerDataHelper->getEventTypesQuery(), $filterValues);

        $this->render('Event/views/eventTypes_index.latte', $this->getAllParams([
            'eventTypes' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/eventTypes',
            'page' => $this->application->getConnectedUser()->getPage(),
            'btn_HistoryBack' => true,
            'btn_Parent'      => "/designer",
        ]));
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
            $this->raiseBadRequest(
                "Invalid EventType {$eventTypeId} in file " . __FILE__ . ' at line ' . __LINE__,
                __FILE__,
                __LINE__
            );
            return false;
        }
        return true;
    }
}
