<?php
declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\helpers\Application;
use app\helpers\Params;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\AbstractController;
use app\services\AuthenticationService;

class UserController extends AbstractController
{
    public function __construct(
        Application $application,
        private AuthenticationService $authService,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

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
        else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    public function signIn(): void
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
        } else $this->raiseMethodNotAllowed(__FILE__, __LINE__);
    }

    public function signOut(): void
    {
        if ($this->application->getConnectedUser()->get()->person === null) {
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
}
