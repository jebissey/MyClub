<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ArwardsDataHelper;
use app\modules\Common\AbstractController;

class ArwardsController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function seeArwards(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!($this->application->getConnectedUser()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $arwardsDataHelper = new ArwardsDataHelper($this->application);
        $this->render('Webmaster/views/arwards.latte', $this->getAllParams([
            'counterNames' => $counterNames = $arwardsDataHelper->getCounterNames(),
            'data' => $arwardsDataHelper->getData($counterNames),
            'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($person),
            'isMyclubWebSite' => WebApp::isMyClubWebSite(),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function setArward(): void
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
            'customName' => FilterInputRule::PersonName->value,
            'name' => FilterInputRule::PersonName->value,
            'detail' => FilterInputRule::HtmlSafeText->value,
            'value' => FilterInputRule::Int->value,
            'idPerson' => FilterInputRule::Int->value,
            'idGroup' => FilterInputRule::Int->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $name = $input['customName'] ?? $input['name'];
        $value = $input['value'];
        $idPerson = $input['idPerson'];
        $idGroup = $input['idGroup'];
        if (
            $name === null
            || $value === null || $value < 0
            || $idPerson === null || $idPerson <= 0
            || $idGroup === null || $idGroup <= 0
        ) $this->redirect('/arwards?error=invalid_data');
        else {
            $this->dataHelper->set('Counter', [
                'Name' => $name,
                'Detail' => $input['detail'],
                'Value' => $value,
                'IdPerson' => $idPerson,
                'IdGroup' => $idGroup
            ]);
            $this->redirect('/arwards?success=true');
        }
    }
}
