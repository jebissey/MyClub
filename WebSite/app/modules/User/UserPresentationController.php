<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserEditPresentationViewModel;
use app\modules\User\viewModels\UserPresentationViewModel;
use app\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
class UserPresentationController extends AbstractController
{
    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function editPresentation(): void
    {
        if ($person = $this->application->getConnectedUser()->person ?? false) {
            if (WebApp::getRequestMethod() !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $row = $this->dataHelper->get('Person', ['Id' => $person->Id]);
            if ($row === false) {
                $this->raiseBadRequest("Unknown person {$person->Id}", __FILE__, __LINE__);
                return;
            }

            $viewModel = new UserEditPresentationViewModel(
                person: $row,
                navItems: $this->getNavItems($person),
                validationMsg: ($this->t)('presentation.edit.validation.noContent'),
                maxZoom: 18,
                layoutParams: $this->getAllParams([]),
            );

            $this->render('User/views/user_edit_presentation.latte', $viewModel->toArray());
        } else {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
        }
    }

    public function savePresentation(): void
    {
        if ($person = $this->application->getConnectedUser()->person ?? false) {
            if (WebApp::getRequestMethod() === 'POST') {
                $schema = [
                    'content' => FilterInputRule::Html->value,
                    'location' => FilterInputRule::Location->value,
                    'inPresentationDirectory' => FilterInputRule::Bool->value,
                    'showPhoneInPresentationDirectory' => FilterInputRule::Bool->value,
                    'showEmailInPresentationDirectory' => FilterInputRule::Bool->value,
                    'myPublicDataInPresentationDirectory' => FilterInputRule::Text->value,
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
                    'ShowPhoneInPresentationDirectory' => $input['showPhoneInPresentationDirectory'] ?? 0,
                    'ShowEmailInPresentationDirectory' => $input['showEmailInPresentationDirectory'] ?? 0,
                    'MyPublicDataInPresentationDirectory' => $input['myPublicDataInPresentationDirectory'] ?? '',
                ], ['Id' => $person->Id]);
                $this->redirect('/user/directory');
            } else {
                $this->application->getErrorManager()->raise(
                    ApplicationError::MethodNotAllowed,
                    'Method ' . WebApp::getRequestMethod() . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__
                );
            }
        } else {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
        }
    }

    public function showPresentation(int $personId): void
    {
        if (!$loggedPerson = $this->application->getConnectedUser()->person) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        $row = $this->dataHelper->get('Person', [
            'Id' => $personId,
            'Inactivated' => 0,
            'InPresentationDirectory' => 1,
        ]);

        if ($row === false) {
            $this->raiseBadRequest("Unknown person {$personId}", __FILE__, __LINE__);
            return;
        }
        /** @var PersonRow $row */
        $person = Person::fromRow($row);

        $viewModel = new UserPresentationViewModel(
            person: $row,
            loggedPerson: $loggedPerson,
            navItems: $this->getNavItems($person),
            userImg_: WebApp::getUserImg($person, new GravatarHandler()),
            maxZoom: 12,
            layoutParams: $this->getAllParams([]),
        );

        $this->render('User/views/user_presentation.latte', $viewModel->toArray());
    }
}
