<?php

namespace app\controllers;

use app\helpers\GroupDataHelper;
use app\helpers\Webapp;

class GroupController extends BaseController implements CrudControllerInterface
{
    private GroupDataHelper $groupDataHelper;

    public function __construct()
    {
        $this->groupDataHelper = new GroupDataHelper();
    }

    public function index()
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            $this->render('app/views/groups/index.latte', $this->params->getAll([
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'layout' => Webapp::getLayout()()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function create()
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {

            $availableAuthorizations = $this->dataHelper->gets('Authorization');
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? Webapp::sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $this->render('app/views/groups/create.latte', $this->params->getAll([
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => Webapp::getLayout()()
                    ]));
                }
                $this->groupDataHelper->insert($name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/groups/create.latte', $this->params->getAll([
                    'availableAuthorizations' => $availableAuthorizations,
                    'layout' => Webapp::getLayout()()
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function edit($id)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null]);
            $group = $this->dataHelper->get('Group', ['Id' => $id]);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $name = isset($_POST['name']) ? Webapp::sanitizeInput($_POST['name']) : '';
                $selfRegistration = isset($_POST['selfRegistration']) ? 1 : 0;
                $selectedAuthorizations = isset($_POST['authorizations']) ? $_POST['authorizations'] : [];

                if (empty($name)) {
                    $this->render('app/views/groups/edit.latte', $this->params->getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => Webapp::getLayout()()
                    ]));
                } else $this->groupDataHelper->update($id, $name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (!$group) $this->application->error499('Group', $id, __FILE__, __LINE__);
                else {
                    $this->render('app/views/groups/edit.latte', $this->params->getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'currentAuthorizations' => array_column($this->dataHelper->gets('GroupAuthorization', ['IdGroup' => $id], 'IdAuthorization'), 'IdAuthorization'),
                        'layout' => Webapp::getLayout()()
                    ]));
                }
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function delete($id)
    {
        if ($this->personDataHelper->getPerson(['PersonManager', 'Webmaster'])) {
            $this->dataHelper->set('Group', ['Inactivated' => 0], ['Id' => $id]);
            $this->flight->redirect('/groups');
        } else $this->application->error403(__FILE__, __LINE__);
    }
}
