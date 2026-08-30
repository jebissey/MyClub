<?php

declare(strict_types=1);

namespace app\modules\Webmaster;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\ArwardsDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Webmaster\viewModels\ArwardsViewModel;

class ArwardsController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function seeArwards(): void
    {
        $person = $this->application->getConnectedUser()->person;
        if (!$this->application->getConnectedUser()->isHomeDesigner()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $arwardsDataHelper = new ArwardsDataHelper($this->application);
        $counterNames = $arwardsDataHelper->getCounterNames();

        $viewModel = new ArwardsViewModel(
            counterNames: array_values($counterNames),
            data: $arwardsDataHelper->getData($counterNames),
            groups: array_values($this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name')),
            layout: $this->getLayout(),
            navItems: $this->getNavItems($person),
            layoutParams: $this->getAllParams([]),
        );

        $this->render('Webmaster/views/arwards.latte', $viewModel->toArray());
    }

    public function setArward(): void
    {
        if (!$this->application->getConnectedUser()->isHomeDesigner()) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
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
        ) {
            $this->redirect('/arwards?error=invalid_data');
        } else {
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
