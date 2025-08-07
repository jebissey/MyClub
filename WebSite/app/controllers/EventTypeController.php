<?php

namespace app\controllers;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\EventDataHelper;
use app\helpers\Params;
use app\helpers\TableControllerHelper;
use app\helpers\WebApp;
use app\interfaces\CrudControllerInterface;

class EventTypeController extends TableController implements CrudControllerInterface
{
    private EventDataHelper $eventDataHelper;
    private TableControllerHelper $tableControllerHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->eventDataHelper = new EventDataHelper($application);
        $this->tableControllerHelper = new TableControllerHelper($application);
    }

    public function index()
    {
        if ($this->connectedUser->get()->IsWebmaster() ?? false) {
            $filterValues = [];
            $filterConfig = [];
            $columns = [
                ['field' => 'EventTypeName', 'label' => 'Nom'],
                ['field' => 'GroupName', 'label' => 'Groupe'],
                ['field' => 'Attributes', 'label' => 'Attributs'],
            ];
            $data = $this->prepareTableData(
                $this->tableControllerHelper->getEventTypesQuery(),
                $filterValues,
                (int)($this->flight->request()->query['tablePage'] ?? 1)
            );

            $this->render('app/views/eventType/index.latte', Params::getAll([
                'eventTypes' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/eventTypes'
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $this->dataHelper->set('EventType', ['Name' => '']);
                $this->flight->redirect('/EventTypes/edit/' . $id);
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function edit($id)
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            $eventType = $this->dataHelper->get('EventType', ['Id', $id], 'Name, IdGroup');
            if (!$eventType) $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, "Invalide EventType: $id in file " . __FILE__ . ' at line ' . __LINE__);
            else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $schema = [
                        'name' => FilterInputRule::HtmlSafeName->value,
                        'idGroup' => FilterInputRule::Int->value,
                        'groups', FilterInputRule::ArrayInt->value
                    ];
                    $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                    $this->eventDataHelper->update(
                        $id,
                        $input['name'] ?? '',
                        $input['idGroup'] ??  throw new RuntimeException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__),
                        $input['groups']
                    );
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $existingAttributes = $this->eventDataHelper->getExistingAttibutes($id);

                    $this->render('app/views/eventType/edit.latte', Params::getAll([
                        'name' => $eventType->Name,
                        'idGroup' => $eventType->IdGroup,
                        'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                        'attributes' => $this->dataHelper->gets('Attribute', [], '*', 'Name'),
                        'existingAttributes' => $existingAttributes
                    ]));
                } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
            }
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        if ($this->connectedUser->get()->isWebmaster() ?? false) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $this->dataHelper->set('EventType', ['Inactivated' => 1], ['Id' => $id]);

                $this->flight->redirect('/eventTypes');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
