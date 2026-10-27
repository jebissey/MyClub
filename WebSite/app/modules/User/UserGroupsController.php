<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\GroupDataHelper;
use app\models\PersonGroupDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserGroupsViewModel;

final class UserGroupsController extends AbstractController
{
    public function __construct(
        Application $application,
        private readonly PersonGroupDataHelper $personGroupDataHelper,
        private readonly GroupDataHelper $groupDataHelper
    ) {
        parent::__construct($application);
    }

    public function groups(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $currentGroups = $this->groupDataHelper->getCurrentGroups($person->Id);

        $viewModel = new UserGroupsViewModel(
            groups: array_values($currentGroups),
            layout: $this->getLayout(),
            navItems: $this->getNavItems($person->Email),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_groups.latte', $viewModel->toArray());
    }

    public function groupsSave(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;

        /** @var array<int, int> $groups */
        $groups = WebApp::getFiltered(
            'groups',
            FilterInputRule::ArrayInt->value,
            $this->flight->request()->data->getData()
        ) ?? [];

        $this->personGroupDataHelper->update($person->Id, $groups);
        $this->redirect('/user');
    }
}
