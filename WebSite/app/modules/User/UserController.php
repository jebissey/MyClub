<?php

declare(strict_types=1);

namespace app\modules\User;

use Flight;
use InvalidArgumentException;
use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\exceptions\EmailException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\modules\Common\AbstractController;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\viewModels\InfoViewModel;
use app\modules\User\viewModels\UserSetPasswordViewModel;
use app\modules\User\viewModels\UserSignInViewModel;

class UserController extends AbstractController
{
    public function __construct(Application $application, private AuthenticationService $authService)
    {
        parent::__construct($application);
    }

    public function forgotPassword(string $encodedEmail): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $email = urldecode($encodedEmail);
        $success = false;
        try {
            $success = $this->authService->handleForgotPassword($email);
        } catch (EmailException $e) {
            Flight::set('message', "Error {$e->getMessage()} with email {$email}");
            Flight::set('code', ApplicationError::BadRequest->value);
            $viewModel = new InfoViewModel(
                content: ($this->t)('message_email_unknown'),
                hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
                timer: 10000,
                previousPage: false,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('Common/views/info.latte', $viewModel->toArray());
            return;
        } catch (InvalidArgumentException $e) {
            $this->raiseBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        }
        if ($success) {
            Flight::set('message', "Password reset email sent to {$email}");
            Flight::set('code', ApplicationError::Ok->value);
            $viewModel = new InfoViewModel(
                content: ($this->t)('message_email_unknown'),
                hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
                timer: 10000,
                previousPage: false,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('Common/views/info.latte', $viewModel->toArray());
        } else {
            Flight::set('message', "Unable to send password reset email to {$email}");
            Flight::set('code', ApplicationError::Error->value);
            $viewModel = new InfoViewModel(
                content: ($this->t)('message_password_reset_failed'),
                hasAuthorization: $this->application->getConnectedUser()->hasAutorization(),
                timer: 30000,
                previousPage: false,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('Common/views/info.latte', $viewModel->toArray());
        }
    }

    public function setPassword(string $token): void
    {
        if (WebApp::getRequestMethod() === 'POST') {
            $newPassword = WebApp::getFiltered('password', FilterInputRule::Password->value, $this->flight->request()->data->getData());
            if (!is_string($newPassword) || $newPassword === '') {
                $this->raiseBadRequest('Invalid password format', __FILE__, __LINE__);
            } elseif ($this->authService->resetPassword($token, $newPassword)) {
                $this->redirect('/', ApplicationError::Ok, 'Votre mot de passe est réinitialisé');
            } else {
                $this->raiseBadRequest('Invalid or expired token', __FILE__, __LINE__);
            }
        } elseif (WebApp::getRequestMethod() === 'GET') {
            $viewModel = new UserSetPasswordViewModel(
                token: $token,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('User/views/user_set_password.latte', $viewModel->toArray());
        } else {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
        }
    }

    public function signIn(): void
    {
        $redirectRaw = $this->flight->request()->query->getData()['redirect'] ?? '/';
        $redirect = is_string($redirectRaw) ? $redirectRaw : '/';
        if (WebApp::getRequestMethod() === 'POST') {
            $result = $this->authService->handleSignIn($this->flight->request()->data->getData());
            if ($result->isSuccess()) {
                $this->application->getConnectedUser()->get();
                $this->redirect($redirect, ApplicationError::Ok, "Sign in succeeded for {$result->getUser()?->Email}");
            } else {
                $this->raiseBadRequest($result->getError(), __FILE__, __LINE__);
            }
        } elseif (WebApp::getRequestMethod() === 'GET') {
            $rememberMeResult = $this->authService->handleRememberMeLogin();
            if ($rememberMeResult && $rememberMeResult->isSuccess()) {
                $this->redirect($redirect, ApplicationError::Ok, "Auto sign in succeeded for {$rememberMeResult->getUser()?->Email}");
                return;
            }
            $viewModel = new UserSignInViewModel(
                redirect: $redirect,
                layoutParams: $this->getAllParams([]),
            );
            $this->render('User/views/user_sign_in.latte', $viewModel->toArray());
        } else {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
        }
    }

    public function signOut(): void
    {
        if ($this->application->getConnectedUser()->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $userEmailRaw = $_SESSION['user'] ?? '';
        $userEmail = is_string($userEmailRaw) ? $userEmailRaw : '';
        $this->authService->signOut();
        $this->application->getConnectedUser()->get();
        $this->redirect('/', ApplicationError::Ok, "Sign out succeeded for {$userEmail}");
    }
}
