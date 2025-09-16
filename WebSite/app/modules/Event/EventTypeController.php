<?php

namespace app\modules\Event;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\IntegrityException;
use app\helpers\Application;
use app\helpers\ErrorManager;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\EventDataHelper;
use app\models\GenericDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\TableController;

class EventTypeController extends TableController
{
    public function __construct(
        Application $application,
        private EventDataHelper $eventDataHelper,
        private TableControllerDataHelper $tableControllerDataHelper,
        private ErrorManager $errorManager,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper,
        GenericDataHelper $genericDataHelper
    ) {
        parent::__construct($application, $genericDataHelper, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function index(): void
    {
        if (!($this->application->getConnectedUser()->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $filterValues = [];
        $filterConfig = [];
        $columns = [
            ['field' => 'EventTypeName', 'label' => 'Nom'],
            ['field' => 'GroupName', 'label' => 'Groupe'],
            ['field' => 'Attributes', 'label' => 'Attributs'],
        ];
        $data = $this->prepareTableData(
            $this->tableControllerDataHelper->getEventTypesQuery(),
            $filterValues,
            (int)($this->flight->request()->query['tablePage'] ?? 1)
        );

        $this->render('Event/views/eventTypes_index.latte', Params::getAll([
            'eventTypes' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/eventTypes'
        ]));
    }

    public function create(): void
    {
        if (!($this->application->getConnectedUser()->get()->isEventDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $id = $this->dataHelper->set('EventType', ['Name' => '']);
        $this->redirect('/EventTypes/edit/' . $id);
    }

    public function edit(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isEventDesigner()) && $this->eventTypeExists($id)) {
            $eventType = $this->dataHelper->get('EventType', ['Id', $id], 'Name, IdGroup');
            $this->render('Event/views/eventType_edit.latte', Params::getAll([
                'name' => $eventType->Name,
                'idGroup' => $eventType->IdGroup,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'attributes' => $this->dataHelper->gets('Attribute', [], '*', 'Name'),
                'existingAttributes' => $this->dataHelper->gets('EventTypeAttribute', ['IdEventType' => $id], 'Id')
            ]));
        }
    }

    public function editSave(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventDesigner()) && $this->eventTypeExists($id)) {
            $schema = [
                'name' => FilterInputRule::HtmlSafeName->value,
                'idGroup' => FilterInputRule::Int->value,
                'groups',
                FilterInputRule::ArrayInt->value
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $this->eventDataHelper->update(
                $id,
                $input['name'] ?? '???',
                $input['idGroup'] ??  throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__),
                $input['groups']
            );
        }
    }

    public function delete(int $id): void
    {
        if ($this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isEventDesigner()) && $this->eventTypeExists($id)) {
            $this->dataHelper->set('EventType', ['Inactivated' => 1], ['Id' => $id]);
            $this->redirect('/eventTypes');
        }
    }

    #region Private functions
    private function eventTypeExists(int $eventTypeId): bool
    {
        $eventType = $this->dataHelper->get('EventType', ['Id', $eventTypeId], 'Id');
        if ($eventType === false) {
            $this->errorManager->raise(ApplicationError::InvalidSetting, "Invalide EventType {$eventTypeId} in file " . __FILE__ . ' at line ' . __LINE__);
            return false;
        }
        return true;
    }
}
