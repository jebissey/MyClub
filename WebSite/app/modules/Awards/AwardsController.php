<?php

declare(strict_types=1);

namespace app\modules\Awards;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\AwardsDataHelper;
use app\modules\Common\AbstractController;
use app\modules\Awards\viewModels\AwardsViewModel;

final class AwardsController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function seeAwards(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isHomeDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $awardsDataHelper = new AwardsDataHelper($this->application);
        $counterNames = $awardsDataHelper->getCounterNames();

        $i18n = $this->translations(['network_error', 'load_persons_error', 'name_required'], 'awards.js.');
        $i18n['choose_person'] = $this->translate('awards.form.choose_person');

        $viewModel = new AwardsViewModel(
            counterNames: $counterNames,
            data: $awardsDataHelper->getData($counterNames),
            groups: $this->getActiveGroups(),
            i18n: $i18n,
            navItems: $this->getNavItems($this->application->getConnectedUser(true)->person->email),
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('Awards/views/awards.latte', $viewModel);
    }

    public function setAward(): void
    {
        if (!$this->application->getConnectedUser()->isHomeDesigner()) {
            $this->errors->forbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->errors->methodNotAllowed(__FILE__, __LINE__);
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
            $this->redirect('/awards?error=invalid_data');
        } else {
            $this->dataHelper->set('Counter', [
                'Name' => $name,
                'Detail' => $input['detail'],
                'Value' => $value,
                'IdPerson' => $idPerson,
                'IdGroup' => $idGroup
            ]);
            $this->redirect('/awards?success=true');
        }
    }
}
