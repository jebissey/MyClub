<?php

namespace app\controllers;

use app\helpers\EventDataHelper;
use app\helpers\TableControllerHelper;

class EventTypeController extends TableController implements CrudControllerInterface
{
    private EventDataHelper $eventDataHelper;
    private TableControllerHelper $tableControllerHelper;

    public function __construct()
    {
        parent::__construct();
        $this->eventDataHelper = new EventDataHelper();
        $this->tableControllerHelper = new TableControllerHelper();
    }

    public function index()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $filterValues = [];
            $filterConfig = [];
            $columns = [
                ['field' => 'EventTypeName', 'label' => 'Nom'],
                ['field' => 'GroupName', 'label' => 'Groupe'],
                ['field' => 'Attributes', 'label' => 'Attributs'],
            ];
            $data = $this->prepareTableData($this->tableControllerHelper->getEventTypesQuery(), $filterValues, $_GET['tablePage'] ?? null);

            $this->render('app/views/eventType/index.latte', $this->params->getAll([
                'eventTypes' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/eventTypes'
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function create()
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $this->dataHelper->set('EventType', ['Name' => '']);
                $this->flight->redirect('/EventTypes/edit/' . $id);
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function edit($id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            $eventType = $this->dataHelper->get('EventType', ['Id', $id]);
            if (!$eventType) $this->application->error499('EventType', $id, __FILE__, __LINE__);
            else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->eventDataHelper->update($id, $_POST['name'], $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null));
                } else if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                    $existingAttributes = $this->eventDataHelper->getExistingAttibutes($id);

                    $this->render('app/views/eventType/edit.latte', $this->params->getAll([
                        'name' => $eventType->Name,
                        'idGroup' => $eventType->IdGroup,
                        'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                        'attributes' => $this->dataHelper->gets('Attribute', [], '*', 'Name'),
                        'existingAttributes' => $existingAttributes
                    ]));
                } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function delete($id)
    {
        if ($this->personDataHelper->getPerson(['Webmaster'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $this->dataHelper->set('EventType', ['Inactivated' => 1], ['Id' => $id]);

                $this->flight->redirect('/eventTypes');
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
