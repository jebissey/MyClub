<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\EmailException;
use app\helpers\Application;
use app\helpers\TranslationManager;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Common\services\AuthenticationService;

class UserController extends AbstractController
{
    public function __construct(Application $application, private AuthenticationService $authService)
    {
        parent::__construct($application);
    }

    public function forgotPassword($encodedEmail): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $email = urldecode($encodedEmail);
        try {
            $success = $this->authService->handleForgotPassword($email);
        } catch (EmailException) {
            $this->flight->setData('message', "Bad email {$email}");
            $this->flight->setData('code', ApplicationError::BadRequest->value);
            $content = $this->languagesDataHelper->translate('message_email_unknown');
            $this->render('Common/views/info.latte', [
                'content' => $content,
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 10000,
                'previousPage' => false,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]);
            return;
        }
        if ($success) {
            $this->flight->setData('message', "Password reset email sent to {$email}");
            $this->flight->setData('code', ApplicationError::Ok->value);
            $content = $this->languagesDataHelper->translate('message_password_reset_sent');
            $this->render('Common/views/info.latte', [
                'content' => $content,
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 10000,
                'previousPage' => false,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]);
        } else {
            $this->flight->setData('message', "Unable to send password reset email to {$email}");
            $content = $this->languagesDataHelper->translate('message_password_reset_failed');
            $this->flight->setData('code', ApplicationError::Error->value);
            $this->render('Common/views/info.latte', [
                'content' => $content,
                'hasAuthorization' => $this->application->getConnectedUser()->hasAutorization() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => 30000,
                'previousPage' => false,
                'page' => $this->application->getConnectedUser()->getPage(),
            ]);
        }
    }

    public function setPassword($token): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = WebApp::getFiltered('password', FilterInputRule::Password->value, $this->flight->request()->data->getData());
            if (!$newPassword)                                               $this->raiseBadRequest('Invalid password format', __FILE__, __LINE__);
            elseif ($this->authService->resetPassword($token, $newPassword)) $this->redirect('/', ApplicationError::Ok, 'Votre mot de passe est réinitialisé');
            else                                                             $this->raiseBadRequest('Invalid or expired token', __FILE__, __LINE__);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') $this->render('User/views/user_set_password.latte', $this->getAllParams([
            'token' => $token,
            'page' => $this->application->getConnectedUser()->getPage()
        ]));
        else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    public function signIn(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->authService->handleSignIn($this->flight->request()->data->getData());
            if ($result->isSuccess()) {
                $this->application->getConnectedUser()->get();
                $this->redirect('/', ApplicationError::Ok, "Sign in succeeded for {$result->getUser()->Email}");
            } else $this->raiseBadRequest($result->getError(), __FILE__, __LINE__);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rememberMeResult = $this->authService->handleRememberMeLogin();
            if ($rememberMeResult && $rememberMeResult->isSuccess()) {
                $this->redirect('/', ApplicationError::Ok, "Auto sign in succeeded for {$rememberMeResult->getUser()->Email}");
                return;
            }
            $lang = TranslationManager::getCurrentLanguage();
            $this->render('User/views/user_sign_in.latte', [
                'href' => '/user/sign/in',
                'userImg' => '👻',
                'userEmail' => '',
                'isAdmin' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'currentVersion' => Application::VERSION,
                'page' => $this->application->getConnectedUser()->getPage(),
                'flag' => TranslationManager::getFlag($lang),
            ]);
        } else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    public function signOut(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseforbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $userEmail = $_SESSION['user'] ?? '';
        $this->authService->signOut();
        $this->application->getConnectedUser()->get();
        $this->redirect('/', ApplicationError::Ok, "Sign out succeeded for {$userEmail}");
    }
}
