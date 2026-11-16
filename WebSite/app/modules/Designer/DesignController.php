<?php

declare(strict_types=1);

namespace app\modules\Designer;

use RuntimeException;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\DesignDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Designer\viewModels\DesignCreateViewModel;
use app\modules\Designer\viewModels\DesignsIndexViewModel;

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
        $connectedUser = $this->application->getConnectedUser();
        if (!$connectedUser->isHomeDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        [$designs, $userVotes] = $this->designDataHelper->getUsersVotes($connectedUser->person->id ?? 0);

        $viewModel = new DesignsIndexViewModel(
            designs: $designs,
            groups: $this->getActiveGroups(),
            userVotes: $userVotes,
            layoutParams: $this->getAllParams(['page' => $connectedUser->getPage()]),
        );

        $this->views->render('Article/views/designs_index.latte', $viewModel);
    }

    public function create(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $viewModel = new DesignCreateViewModel(
            groups: $this->getActiveGroups(),
            layoutParams: $this->getAllParams([
                'page' => $this->application->getConnectedUser()->getPage(),
            ]),
        );

        $this->views->render('Article/views/design_create.latte', $viewModel);
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
}
