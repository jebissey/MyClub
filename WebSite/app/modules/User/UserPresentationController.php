<?php

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Params;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;

class UserPresentationController extends AbstractController
{
    public function editPresentation(): void
    {
        if ($person = $this->application->getConnectedUser()->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $this->render('User/views/user_edit_presentation.latte', Params::getAll([
                'person' => $person,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function savePresentation()
    {
        if ($person = $this->application->getConnectedUser()->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'content' => FilterInputRule::Html->value,
                    'location' => FilterInputRule::Location->value,
                    'inPresentationDirectory' => FilterInputRule::Bool->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $presentation = $input['content'] ?? '???';
                $location =  $input['location'] ?? '???';
                $inDirectory = $input['inPresentationDirectory'] ?? 0;

                $this->dataHelper->set('Person', [
                    'Presentation' => $presentation,
                    'PresentationLastUpdate' => date('Y-m-d H:i:s'),
                    'Location' => $location,
                    'InPresentationDirectory' => $inDirectory,
                ], ['Id' => $person->Id]);
                $this->redirect('/user/directory');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showPresentation($personId)
    {
        if ($loggedPerson = $this->application->getConnectedUser()->get()->person ?? false) {
            $person = $this->dataHelper->get('Person', [
                'Id' => $personId,
                'Inactivated' => 0,
                'InPresentationDirectory' => 1
            ]);
            if (!$person) {
                $this->raiseBadRequest("Unknown person {$personId}", __FILE__,  __LINE__);
                return;
            }

            $this->render('User/views/user_presentation.latte', Params::getAll([
                'person' => $person,
                'loggedPerson' => $loggedPerson,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
