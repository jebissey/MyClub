<?php

declare(strict_types=1);

namespace app\modules\Designer;

use RuntimeException;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\DesignDataHelper;
use app\modules\Common\AbstractController;

final class DesignController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly DesignDataHelper $designDataHelper
    ) {
        parent::__construct($application);
    }

    public function index(): void
    {
        if (!$this->application->getConnectedUser()->isHomeDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        [$designs, $userVotes] = $this->designDataHelper->getUsersVotes($this->application->getConnectedUser()->person->id ?? 0);

        $this->views->render('Article/views/designs_index.latte', $this->getAllParams([
            'designs' => $designs,
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'userVotes' => $userVotes,
            'page' => $this->application->getConnectedUser()->getPage(),
            'btnHistoryBack' => true,
            'btnParent'      => "/designer",
        ]));
    }

    public function create(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $this->views->render('Article/views/design_create.latte', $this->getAllParams([
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function save(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
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
            'IdPerson' => $this->application->getConnectedUser()->person->id ?? 0,
            'Name' => $filterValues['name'] ?? '',
            'Detail' => $filterValues['detail'] ?? '',
            'NavBar' => $filterValues['navbar'] ?? '',
            'Status' => 'UnderReview',
            'OnlyForMembers' => $filterValues['onlyForMembers'] ?? 1,
            'IdGroup' => $filterValues['idGroup']
        ];
        $this->dataHelper->set(
            'Design',
            $values,
            ['Id' => $filterValues['id'] ?? throw new RuntimeException('Missing Id in file ' . __FILE__ . ' at line ' . __LINE__)]
        );

        $this->redirect('/designs');
    }

    public function help(): void
    {
        $this->renderHelp('Help_Designer', fn($u) => $u->isDesigner(), __FILE__, __LINE__);
    }

    public function homeDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'designer';
            $this->views->render('Designer/views/designer.latte', $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]));
        }
    }
}
