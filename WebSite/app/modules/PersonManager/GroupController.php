<?php

declare(strict_types=1);

namespace app\modules\PersonManager;

use Throwable;

use app\enums\FilterInputRule;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;

class GroupController extends AbstractController
{
    public function __construct(
        Application $application,
        private GroupDataHelper $groupDataHelper,
        private PersonGroupDataHelper $personGroupDataHelper,
        private MessageDataHelper $messageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function groupCreate(): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null]);
        $this->render('PersonManager/views/group_create.latte', Params::getAll([
            'availableAuthorizations' => $availableAuthorizations,
            'layout' => $this->getLayout(),
            'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function groupCreateSave(): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null]);
        $schema = [
            'name' => FilterInputRule::HtmlSafeName->value,
            'selfRegistration' => FilterInputRule::Int->value,
            'authorizations' => FilterInputRule::ArrayInt->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $name = $input['name'] ?? '???';
        $selfRegistration = $input['selfRegistration'] ?? 0;
        if ($name === '???') {
            $this->render('PersonManager/views/group_create.latte', Params::getAll([
                'availableAuthorizations' => $availableAuthorizations,
                'error' => 'Le nom du groupe est requis',
                'layout' => $this->getLayout(),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
            return;
        }
        $selectedAuthorizations = $input['authorizations'] ?? [];
        $this->groupDataHelper->insert($name, $selfRegistration, $selectedAuthorizations);
    }

    public function groupDelete(int $id): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($id === 1) {
            $this->raiseBadRequest("Group {$id} can't be inactivatd", __FILE__, __LINE__);
            return;
        }
        try {
            $this->dataHelper->set('Group', ['Inactivated' => 1], ['Id' => $id]);
            $this->redirect('/groups');
        } catch (QueryException $e) {
            $this->raiseBadRequest("Error {$e->getMessage()}", __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->raiseError($e->getMessage(), __FILE__, __LINE__);
        }
    }

    public function groupEdit(int $id): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($id === 1) {
            $this->raiseBadRequest("Group ({$id}) can't be edited", __FILE__, __LINE__);
            return;
        }
        $group = $this->dataHelper->get('Group', ['Id' => $id], 'Name, SelfRegistration');
        if (!$group) $this->raiseBadRequest("Unknwon group $id", __FILE__, __LINE__);
        else {
            $this->render('PersonManager/views/group_edit.latte', Params::getAll([
                'group' => $group,
                'availableAuthorizations' => $this->dataHelper->gets('Authorization', ['Id <> 1' => null], '*', 'Name'),
                'currentAuthorizations' => array_column($this->dataHelper->gets('GroupAuthorization', ['IdGroup' => $id], 'IdAuthorization'), 'IdAuthorization'),
                'layout' => $this->getLayout(),
                'isMyclubWebSite' => WebApp::isMyClubWebSite(),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }

    public function groupEditSave(int $id): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($id === 1) {
            $this->raiseBadRequest("Group {$id} can't be updated", __FILE__, __LINE__);
            return;
        }
        $availableAuthorizations = $this->dataHelper->gets('Authorization', ['Id <> 1' => null], '*', 'Name');
        $group = $this->dataHelper->get('Group', ['Id' => $id], 'Name, SelfRegistration');
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
                'layout' => $this->getLayout(),
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        } else $this->groupDataHelper->update($id, $name, $selfRegistration, $selectedAuthorizations);
    }

    public function groupIndex(): void
    {
        if (!($this->application->getConnectedUser()->isGroupManager() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('PersonManager/views/groups_index.latte', Params::getAll([
            'groups' => $this->groupDataHelper->getGroupsWithAuthorizations($this->application->getConnectedUser()),
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function showGroupChat($groupId): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $group = $this->dataHelper->get('Group', ['Id' => $groupId], 'Id, Name');
        if (!$group) {
            $this->raiseBadRequest("Le groupe {$groupId} n'existe pas", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null || !$this->personGroupDataHelper->isPersonInGroup($connectedUser->person->Id, $groupId)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        $person = $connectedUser->person;
        $person->UserImg = WebApp::getUserImg($person, new GravatarHandler());
        $this->render('Common/views/chat.latte', Params::getAll([
            'article' => null,
            'event' => null,
            'group' => $group,
            'messages' => $this->messageDataHelper->getGroupMessages($groupId),
            'person' => $person,
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

}
