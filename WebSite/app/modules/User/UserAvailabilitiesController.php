<?php

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class UserAvailabilitiesController extends AbstractController
{
    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    public function availabilities(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $currentAvailabilities = json_decode($person->Availabilities ?? '', true);
        $this->render('User/views/user_availabilities.latte', Params::getAll(['currentAvailabilities' => $currentAvailabilities]));
    }

    public function availabilitiesSave(): void
    {
        $person = $this->application->getConnectedUser()->get(1)->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $availabilities = WebApp::getFiltered('availabilities', FilterInputRule::CheckboxMatrix->value, $this->flight->request()->data->getData()) ?? '';
        if ($availabilities != '') $this->dataHelper->set('Person', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
        $this->redirect('/user');
    }
}
