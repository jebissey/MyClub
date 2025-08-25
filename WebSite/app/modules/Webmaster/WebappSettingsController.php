<?php
namespace app\modules\Webmaster;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class WebappSettingsController extends AbstractController
{
    private $settingsKeys = [
        'Help_admin' => 'Aide administrateur',
        'Help_eventDesigner' => 'Aide concepteur d\'événements',
        'Help_eventManager' => 'Aide gestionnaire d\'événements',
        'Help_home' => 'Aide accueil',
        'Help_homeDesigner' => 'Aide concepteur d\'accueil',
        'Help_personManager' => 'Aide gestionnaire de personnes',
        'Help_user' => 'Aide utilisateur',
        'Help_visitorInsights' => 'Aide statistiques visiteurs',
        'Help_webmaster' => 'Aide webmaster',
        'Home_footer' => 'Pied de page d\'accueil',
        'Home_header' => 'En-tête d\'accueil',
        'Error_403' => 'Page d\'erreur 403',
        'Error_404' => 'Page d\'erreur 404',
        'Error_500' => 'Page d\'erreur 500'
    ];
    
    public function __construct(Application $application)
    {
        parent::__construct($application);
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
