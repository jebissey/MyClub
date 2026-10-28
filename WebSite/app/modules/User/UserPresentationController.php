<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\GravatarHandler;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\MemberDirectoryDataHelper;
use app\modules\Common\AbstractController;
use app\modules\User\viewModels\UserEditPresentationViewModel;
use app\modules\User\viewModels\UserPresentationViewModel;
use app\modules\Common\valueObjects\Person;

/**
 * @phpstan-import-type PersonRow from Person
 */
final class UserPresentationController extends AbstractController
{
    public function __construct(Application $application, private readonly MemberDirectoryDataHelper $memberDirectoryDataHelper)
    {
        parent::__construct($application);
    }

    public function editPresentation(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $person = $this->application->getConnectedUser(true)->person;

        $row = $this->dataHelper->get(
            'Member',
            ['Id' => $person->id],
            'Presentation, Location, InPresentationDirectory, ShowPhoneInPresentationDirectory,' .
                'showEmailInPresentationDirectory, myPublicDataInPresentationDirectory'
        );
        if ($row === false) {
            $this->errors->badRequest("Unknown person {$person->id}", __FILE__, __LINE__);
            return;
        }

        $viewModel = new UserEditPresentationViewModel(
            person: $row,
            navItems: $this->getNavItems($person->email),
            validationMsg: ($this->t)('presentation.edit.validation.noContent'),
            maxZoom: 18,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_edit_presentation.latte', $viewModel->toArray());
    }

    public function savePresentation(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

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

        $this->dataHelper->set('Member', [
            'Presentation' => $presentation,
            'PresentationLastUpdate' => date('Y-m-d H:i:s'),
            'Location' => $location,
            'InPresentationDirectory' => $inDirectory,
            'ShowPhoneInPresentationDirectory' => $input['showPhoneInPresentationDirectory'] ?? 0,
            'ShowEmailInPresentationDirectory' => $input['showEmailInPresentationDirectory'] ?? 0,
            'MyPublicDataInPresentationDirectory' => $input['myPublicDataInPresentationDirectory'] ?? '',
        ], ['Id' => $this->application->getConnectedUser(true)->person->id]);
        $this->redirect('/user/directory');
    }

    public function showPresentation(int $personId): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isConnected(), __FILE__, __LINE__)) {
            return;
        }

        $row = $this->dataHelper->get('Member', [
            'Id' => $personId,
            'Inactivated' => 0,
            'InPresentationDirectory' => 1,
        ], 'Id');
        if ($row === false) {
            $this->errors->badRequest("Unknown person {$personId}", __FILE__, __LINE__);
            return;
        }

        $person = $this->memberDirectoryDataHelper->getLocatedMember($personId);

        $viewModel = new UserPresentationViewModel(
            person: $person,
            loggedPerson: $this->application->getConnectedUser(true)->person,
            navItems: $this->getNavItems($person->email),
            personImg: WebApp::getUserImg(
                To::bool($person->useGravatar),
                $person->email,
                $person->avatar,
                new GravatarHandler()
            ),
            maxZoom: 12,
            layoutParams: $this->getAllParams([]),
        );

        $this->views->render('User/views/user_presentation.latte', $viewModel->toArray());
    }
}
