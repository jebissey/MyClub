<?php

namespace app\modules\Webmaster;

use app\enums\Help;
use app\enums\Message;
use app\helpers\Application;
use app\helpers\Params;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;

class WebappSettingsController extends AbstractController
{
    private $settingsKeys = [
        'Error_403' => 'Error 403',
        'Error_404' => 'Error 404',
        'Error_500' => 'Error 500',
    ];

    public function __construct(
        Application $application,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
        foreach (Help::cases() as $case) {
            $this->settingsKeys['Help_' . $case->name] = $this->languagesDataHelper->translate('Help_' . $case->value);
        }
        foreach (Message::cases() as $case) {
            $this->settingsKeys['Message_' . $case->name] = $this->languagesDataHelper->translate('Message_' . $case->value);
        }
        $this->settingsKeys['Home_header'] = $this->languagesDataHelper->translate('Home_header');
        $this->settingsKeys['Home_footer'] = $this->languagesDataHelper->translate('Home_footer');
    }

    public function editSettings(): void
    {
        if (!($this->application->getConnectedUser()->get()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $settings = [];
        foreach ($this->settingsKeys as $key => $label) {
            $result = $this->dataHelper->get('Settings', ['Name' => $key], 'Value');
            if ($result === false) {
                $this->dataHelper->set('Settings', ['Value' => '', 'Name' => $key]);
                $settings[$key] = '';
            } else $settings[$key] = $result->Value ?? '';
        }

        $this->render('Webmaster/views/webappSettings.latte', Params::getAll([
            'navItems' => $this->getNavItems($this->application->getConnectedUser()->person),
            'settingsKeys' => $this->settingsKeys,
            'settings' => $settings
        ]));
    }

    public function saveSettings()
    {
        if (!($this->application->getConnectedUser()->get()->isHomeDesigner() ?? false)) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $input = $this->flight->request()->data->getData();
        foreach ($this->settingsKeys as $key => $label) {
            if (isset($input[$key])) {
                $value = $input[$key] ?? '';
                $existing = $this->dataHelper->get('Settings', ['Name' => $key], 'Id');
                if ($existing) $this->dataHelper->set('Settings', ['Value' => $value], ['Name' => $key]);
                else           $this->dataHelper->set('Settings', ['Value' => $value, 'Name' => $key]);
            }
        }
        $this->redirect('/settings');
    }
}
