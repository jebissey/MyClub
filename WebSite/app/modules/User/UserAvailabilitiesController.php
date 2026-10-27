<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserAvailabilitiesViewModel;

final class UserAvailabilitiesController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function availabilities(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;
        $row = $this->dataHelper->get('Member', ['Id' => $person->Id], 'Availabilities');
        /** @var object{Availabilities: string|null}|false $row */
        $availabilitiesJson = $row !== false ? ($row->Availabilities ?? '') : '';

        /** @var array<int, array<string, mixed>>|null $currentAvailabilities */
        $currentAvailabilities = json_decode($availabilitiesJson, true);

        $viewModel = new UserAvailabilitiesViewModel(
            navItems: $this->getNavItems($person->Email),
            currentAvailabilities: is_array($currentAvailabilities) ? $currentAvailabilities : null,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_availabilities.latte', $viewModel->toArray());
    }

    public function availabilitiesSave(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $availabilities = WebApp::getFiltered(
            'availabilities',
            FilterInputRule::CheckboxMatrix->value,
            $this->flight->request()->data->getData()
        ) ?? '';
        $person = $this->application->getConnectedUser(true)->person;
        if ($availabilities != '') {
            $this->dataHelper->set('Member', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
        }
        $this->redirect('/user');
    }
}
