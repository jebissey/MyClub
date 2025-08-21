<?php

namespace app\modules\PersonManager;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\interfaces\CrudControllerInterface;
use app\models\GroupDataHelper;
use app\modules\Common\AbstractController;

class GroupController extends AbstractController implements CrudControllerInterface
{
    private GroupDataHelper $groupDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->groupDataHelper = new GroupDataHelper($application);
    }

    public function index()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->render('PersonManager/views/groups_index.latte', Params::getAll([
                'groups' => $this->groupDataHelper->getGroupsWithAuthorizations(),
                'layout' => $this->getLayout(),
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function create()
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {

            $availableAuthorizations = $this->dataHelper->gets('Authorization',['Id <> 1' => null]);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'name' => FilterInputRule::HtmlSafeName->value,
                    'selfRegistration' => FilterInputRule::Int->value,
                    'authorizations' => FilterInputRule::ArrayInt->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $name = $input['name'] ?? '???';
                $selfRegistration = $input['selfRegistration'] ?? 0;
                $selectedAuthorizations = $input['authorizations'] ?? [];
                if (empty($name)) {
                    $this->render('PersonManager/views/groups/create.latte', Params::getAll([
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => $this->getLayout()
                    ]));
                }
                $this->groupDataHelper->insert($name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('PersonManager/views/groups/create.latte', Params::getAll([
                    'availableAuthorizations' => $availableAuthorizations,
                    'layout' => $this->getLayout()
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function edit($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null]);
            $group = $this->dataHelper->get('Group', ['Id' => $id], 'Name, SelfRegistration');

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'name' => FilterInputRule::HtmlSafeName->value,
                    'selfRegistration' => FilterInputRule::Int->value,
                    'authorizations' => FilterInputRule::ArrayInt->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $name = $input['name'] ?? '???';
                $selfRegistration = $input['selfRegistration'] ?? 0;
                $selectedAuthorizations = $input['authorizations'] ?? [];

                if (empty($name)) {
                    $this->render('PersonManager/views/group_edit.latte', Params::getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'error' => 'Le nom du groupe est requis',
                        'layout' => $this->getLayout()
                    ]));
                } else $this->groupDataHelper->update($id, $name, $selfRegistration, $selectedAuthorizations);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (!$group) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknwon group $id in file " . __FILE__ . ' at line ' . __LINE__);
                else {
                    $this->render('PersonManager/views/group_edit.latte', Params::getAll([
                        'group' => $group,
                        'availableAuthorizations' => $availableAuthorizations,
                        'currentAuthorizations' => array_column($this->dataHelper->gets('GroupAuthorization', ['IdGroup' => $id], 'IdAuthorization'), 'IdAuthorization'),
                        'layout' => $this->getLayout()
                    ]));
                }
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function delete($id)
    {
        if (($this->connectedUser->get()->isPersonManager() ?? false) || $this->connectedUser->isWebmaster() ?? false) {
            $this->dataHelper->set('Group', ['Inactivated' => 0], ['Id' => $id]);
            $this->flight->redirect('/groups');
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
