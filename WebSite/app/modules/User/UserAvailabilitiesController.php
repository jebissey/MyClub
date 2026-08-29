<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class UserAvailabilitiesController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function availabilities(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $row = $this->dataHelper->get('Person', ['Id' => $person->Id], 'Availabilities');
        /** @var object{Availabilities: string|null}|false $row */
        $availabilitiesJson = $row !== false ? ($row->Availabilities ?? '') : '';

        $currentAvailabilities = json_decode($availabilitiesJson, true);
        $this->render('User/views/user_availabilities.latte', $this->getAllParams([
            'currentAvailabilities' => $currentAvailabilities,
            'page' => $this->application->getConnectedUser()->getPage(1),
            'btn_HistoryBack' => true,
            'btn_Parent' => "/user",
        ]));
    }

    public function availabilitiesSave(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if ($person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $availabilities = WebApp::getFiltered(
            'availabilities',
            FilterInputRule::CheckboxMatrix->value,
            $this->flight->request()->data->getData()
        ) ?? '';
        if ($availabilities != '') {
            $this->dataHelper->set('Person', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
        }
        $this->redirect('/user');
    }
}
