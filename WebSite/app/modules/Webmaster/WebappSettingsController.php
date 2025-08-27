<?php

namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\enums\Help;
use app\enums\Message;
use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class WebappSettingsController extends AbstractController
{
    private $settingsKeys = [
        'Error_403' => 'Error 403',
        'Error_404' => 'Error 404',
        'Error_500' => 'Error 500',
    ];

    public function __construct(Application $application)
    {
        parent::__construct($application);
        foreach (Help::cases() as $case) {
            $this->settingsKeys['Help_' . $case->name] = $this->languagesDataHelper->translate('Help_' . $case->value);
        }
        foreach (Message::cases() as $case) {
            $this->settingsKeys['Message_' . $case->name] = $this->languagesDataHelper->translate('Message_' . $case->value);
        }
        $this->settingsKeys['Home_header'] = $this->languagesDataHelper->translate('Home_header');
        $this->settingsKeys['Home_footer'] = $this->languagesDataHelper->translate('Home_footer');
    }

    public function editSettings()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $settings = [];
                foreach ($this->settingsKeys as $key => $label) {
                    $result = $this->dataHelper->get('Settings', ['Name' => $key], 'Value');
                    if ($result === false) {
                        $this->dataHelper->set('Settings', ['Value' => '', 'Name' => $key]);
                        $settings[$key] = '';
                    } else $settings[$key] = $result->Value ?? '';
                }

                $this->render('Webmaster/views/webappSettings.latte', Params::getAll([
                    'navItems' => $this->getNavItems($person),
                    'settingsKeys' => $this->settingsKeys,
                    'settings' => $settings
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function saveSettings()
    {
        if ($this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
