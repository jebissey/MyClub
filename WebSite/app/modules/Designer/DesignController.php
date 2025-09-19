<?php

declare(strict_types=1);

namespace app\modules\Designer;

use RuntimeException;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\DesignDataHelper;
use app\modules\Common\AbstractController;

class DesignController extends AbstractController
{

    public function __construct(
        Application $application,
        private DesignDataHelper $designDataHelper
    ) {
        parent::__construct($application);
    }

    public function index()
    {
        if (!($this->application->getConnectedUser()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        [$designs, $userVotes] = $this->designDataHelper->getUsersVotes($this->application->getConnectedUser()->person->Id);

        $this->render('Article/views/designs_index.latte', Params::getAll([
            'designs' => $designs,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'userVotes' => $userVotes,
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function create()
    {
        if (!($this->application->getConnectedUser()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('Article/views/design_create.latte', Params::getAll([
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function save()
    {
        if (!($this->application->getConnectedUser()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'id' => FilterInputRule::Int->value,
            'name' => FilterInputRule::HtmlSafeName->value,
            'detail' => FilterInputRule::HtmlSafeName->value,
            'navbar' => FilterInputRule::Content->value,
            'onlyForMembers' => FilterInputRule::Int->value,
            'idGroup' => FilterInputRule::Int->value,
        ];
        $filterValues = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $values = [
            'IdPerson' => $this->application->getConnectedUser()->person->Id,
            'Name' => $filterValues['name'] ?? '',
            'Detail' => $filterValues['detail'] ?? '',
            'NavBar' => $filterValues['navbar'] ?? '',
            'Status' => 'UnderReview',
            'OnlyForMembers' => $filterValues['onlyForMembers'] ?? 1,
            'IdGroup' => $filterValues['idGroup']
        ];
        $this->dataHelper->set('Design', $values, ['Id' => $filterValues['id'] ?? throw new RuntimeException('Missing Id in file ' . __FILE__ . ' at line ' . __LINE__)]);

        $this->redirect('/designs');
    }

    public function helpDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner())) {
            $this->render('Common/views/info.latte', [
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_designer'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->application->getConnectedUser()->isDesigner() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true,
                'page' => $this->application->getConnectedUser()->getPage()
            ]);
        }
    }

    public function homeDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner())) {
            $_SESSION['navbar'] = 'designer';
            $this->render('Designer/views/designer.latte', Params::getAll([
                'page' => $this->application->getConnectedUser()->getPage()
            ]));
        }
    }
}
