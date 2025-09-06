<?php

namespace app\modules\User;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\enums\YesNo;
use app\helpers\Application;
use app\helpers\News;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\ArticleDataHelper;
use app\models\AttributeDataHelper;
use app\models\EventDataHelper;
use app\models\EventTypeDataHelper;
use app\models\GroupDataHelper;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Common\AbstractController;
use app\services\AuthenticationService;
use app\services\EmailService;

class UserController extends AbstractController
{
    private News $news;
    private AuthenticationService $authService;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->news =  new News([
            new ArticleDataHelper($application),
            new SurveyDataHelper($application),
            new EventDataHelper($application),
            new MessageDataHelper($application),
            new PersonDataHelper($application),
        ]);
        $this->authService = new AuthenticationService($application);
    }

    #region Sign
    public function forgotPassword($encodedEmail): void
    {
        $email = urldecode($encodedEmail);
        $success = $this->authService->handleForgotPassword($email);
        if ($success) $this->redirect('/', ApplicationError::Ok, 'Votre mot de passe est réinitialisé');
        else $this->raiseError('Unable to send password reset email', __FILE__, __LINE__);
    }

    public function setPassword($token): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = WebApp::getFiltered('password', FilterInputRule::Password->value, $this->flight->request()->data->getData());
            if (!$newPassword)                                               $this->raiseBadRequest('Invalid password format', __FILE__, __LINE__);
            elseif ($this->authService->resetPassword($token, $newPassword)) $this->redirect('/', ApplicationError::Ok, 'Votre mot de passe est réinitialisé');
            else                                                             $this->raiseBadRequest('Invalid or expired token', __FILE__, __LINE__);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') $this->render('User/views/user_set_password.latte', Params::getAll(['token' => $token,]));
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->authService->handleSignIn($this->flight->request()->data->getData());
            if ($result->isSuccess()) $this->redirect('/', ApplicationError::Ok, "Sign in succeeded for {$result->getUser()->Email}");
            else $this->raiseBadRequest($result->getError(), __FILE__, __LINE__);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rememberMeResult = $this->authService->handleRememberMeLogin();
            if ($rememberMeResult && $rememberMeResult->isSuccess()) {
                $this->redirect('/', ApplicationError::Ok, "Auto sign in succeeded for {$rememberMeResult->getUser()->Email}");
                return;
            }
            $this->render('User/views/user_sign_in.latte', [
                'href' => '/user/sign/in',
                'userImg' => '🫥',
                'userEmail' => '',
                'isAdmin' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'currentVersion' => Application::VERSION
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid');
    }

    public function signOut(): void
    {
        if ($this->connectedUser->get()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $userEmail = $_SESSION['user'] ?? '';
        $this->authService->signOut();
        $this->redirect('/', ApplicationError::Ok, "Sign out succeeded for {$userEmail}");
    }
    #endregion


    #region Data user
    public function user(): void
    {
        if ($this->connectedUser->get()->person === false) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $_SESSION['navbar'] = 'user';
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->render('User/views/user.latte', Params::getAll(['page' => '']));
    }

    public function account(): void
    {
        if ($this->connectedUser->get(1)->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->connectedUser->person;
        $this->render('User/views/user_account.latte', Params::getAll([
            'readOnly' => $person->Imported == 1 ? true : false,
            'email' => filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?: '',
            'firstName' => WebApp::sanitizeInput($person->FirstName),
            'lastName' => WebApp::sanitizeInput($person->LastName),
            'nickName' => WebApp::sanitizeInput($person->NickName ?? ''),
            'avatar' => WebApp::sanitizeInput($person->Avatar ?? ''),
            'useGravatar' => WebApp::sanitizeInput($person->UseGravatar, $this->application->enumToValues(YesNo::class), YesNo::No->value),
            'emojis' => Application::EMOJI_LIST,
            'isSelfEdit' => true,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
        ]));
    }

    public function accountSave(): void
    {
        $person = $this->connectedUser->get(1)->person;
        if ($person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $schema = [
            'email' => FilterInputRule::Email->value,
            'firstName' => FilterInputRule::PersonName->value,
            'lastName' => FilterInputRule::PersonName->value,
            'nickName' => FilterInputRule::HtmlSafeName->value,
            'useGravatar' => $this->application->enumToValues(YesNo::class),
            'avatar' => FilterInputRule::Avatar->value,
        ];
        $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $this->dataHelper->set('Person', [
            'FirstName' => $input['firstName'] ?? '???',
            'LastName' => $input['lastName'] ?? '???',
            'NickName' => $input['nickName'] ?? '',
            'Avatar' => ($input['useGravatar'] ?? YesNo::No->value) == YesNo::Yes->value ? '' : $input['avatar'] ?? '🤔',
            'useGravatar' => $input['useGravatar'] ?? YesNo::No->value,
        ], ['Id' => $person->Id]);
        if ($person->Imported == 0) {
            $email = urldecode($input['email'] ?? '');
            $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $person->Id]);
            $_SESSION['user'] = $email;
        }
        $this->redirect('/user');
    }

    public function availabilities(): void
    {
        if ($this->connectedUser->get()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $availabilities = WebApp::getFiltered('availabilities', FilterInputRule::CheckboxMatrix->value, $this->flight->request()->data->getData()) ?? '';
                if ($availabilities != '') $this->dataHelper->set('Person', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
                $this->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentAvailabilities = json_decode($person->Availabilities ?? '', true);
                $this->render('User/views/user_availabilities.latte', Params::getAll(['currentAvailabilities' => $currentAvailabilities]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function availabilitiesSave(): void
    {
        $person = $this->connectedUser->get(1)->person;
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

    public function preferences(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = WebApp::getFiltered('preferences', FilterInputRule::CheckboxMatrix->value, $this->flight->request()->data->getData()) ?? '';
                $this->dataHelper->set('Person', ['preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
                $this->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $eventTypes = (new EventTypeDataHelper($this->application))->getsFor($person->Id);
                $eventTypesWithAttributes = [];
                $attributeDataHelper = new AttributeDataHelper($this->application);
                foreach ($eventTypes as $eventType) {
                    $eventType->Attributes = $attributeDataHelper->getAttributesOf($eventType->Id);
                    $eventTypesWithAttributes[] = $eventType;
                }

                $this->render('User/views/user_preferences.latte', Params::getAll([
                    'currentPreferences' => json_decode($person->Preferences ?? '', true),
                    'eventTypes' => $eventTypesWithAttributes
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function groups(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $groups = WebApp::getFiltered('groups', FilterInputRule::ArrayInt->value, $this->flight->request()->data->getData());
                (new PersonGroupDataHelper($this->application))->update($person->Id, $groups ?? []);
                $this->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentGroups = (new GroupDataHelper($this->application))->getCurrentGroups($person->Id);

                $this->render('User/views/user_groups.latte', Params::getAll([
                    'groups' => $currentGroups,
                    'layout' => $this->getLayout(),
                    'navItems' => $this->getNavItems($connectedUser->person ?? false),
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function editNotepad(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->raiseMethodNotAllowed(__FILE__, __LINE__);
                return;
            }
            $this->render('User/views/user_notepad.latte', Params::getAll([
                'notepad' => $person->Notepad,
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function saveNotepad()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'content' => FilterInputRule::Html->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $notepad = $input['content'] ?? '???';

                $this->dataHelper->set('Person', [
                    'Notepad' => $notepad,
                ], ['Id' => $person->Id]);
                $this->redirect('/user');
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showDirectory()
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            $groupParam = $this->flight->request()->query['group'] ?? null;
            $selectedGroup = ($groupParam !== null && ctype_digit((string)$groupParam)) ? (int)$groupParam : null;
            if ($selectedGroup) $persons = (new PersonDataHelper($this->application))->getPersonsInGroupForDirectory($selectedGroup);
            else {
                $persons = $this->dataHelper->gets('Person', [
                    'InPresentationDirectory' => 1,
                    'Inactivated' => 0
                ], 'Id, LastName, FirstName, NickName, UseGravatar, Avatar, Email');
            }
            $groupCounts = (new GroupDataHelper($this->application))->getGroupCount();
            $this->render('User/views/users_directory.latte', Params::getAll([
                'persons' => $persons,
                'navItems' => $this->getNavItems($person),
                'loggedPerson' => $person,
                'groups' => $this->dataHelper->gets('Group', ['Inactivated' => 0], 'Id, Name', 'Name'),
                'groupCounts' => $groupCounts,
                'selectedGroup' => $selectedGroup,
            ]));
        } elseif ($person == '') $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Il faut être connecté pour pouvoir consulter le trombinoscope', 5000);
        else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function showMap()
    {
        if ($person = $this->connectedUser->get()->person ?? false) {
            $members = $this->dataHelper->gets('Person', [
                'InPresentationDirectory' => 1,
                'Location IS NOT NULL' => null,
                'Inactivated' => 0
            ]);
            $locationData = [];
            foreach ($members as $member) {
                if (!empty($member->Location) && preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', $member->Location)) {
                    list($lat, $lng) = explode(',', $member->Location);
                    $locationData[] = [
                        'id' => $member->Id,
                        'name' => $member->FirstName . ' ' . $member->LastName,
                        'nickname' => $member->NickName,
                        'avatar' => $member->Avatar,
                        'useGravatar' => $member->UseGravatar,
                        'email' => $member->Email,
                        'lat' => trim($lat),
                        'lng' => trim($lng)
                    ];
                }
            }

            $this->render('User/views/users_map.latte', Params::getAll([
                'locationData' => $locationData,
                'membersCount' => count($locationData),
                'navItems' => $this->getNavItems($person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
    #endregion 

    public function help(): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            $this->render('Common/views/info.latte', Params::getAll([
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_user'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->hasAutorization(),
                'currentVersion' => Application::VERSION,
                'timer' => 0,
                'previousPage' => true
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
