<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\MessageDataHelper;
use app\modules\Common\AbstractController;

class UserMessagesController extends AbstractController
{
    public function __construct(
        Application $application,
        private MessageDataHelper $messageDataHelper,
    ) {
        parent::__construct($application);
    }

    public function showMessages(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person ?? false) {
            $searchMode = WebApp::getFiltered('from', $this->application->enumToValues(\app\enums\Period::class), $this->flight->request()->query->getData()) ?: \app\enums\Period::Signout->value;

            if ($searchMode === Period::Signin->value) $searchFrom = $connectedUser->person->LastSignIn ?? '';
            elseif ($searchMode === Period::Signout->value) $searchFrom = $connectedUser->person->LastSignOut ?? '';
            elseif ($searchMode === Period::Week->value)    $searchFrom = date('Y-m-d H:i:s', strtotime('-1 week'));
            elseif ($searchMode === Period::Month->value)   $searchFrom = date('Y-m-d H:i:s', strtotime('-1 month'));
            elseif ($searchMode === Period::Quarter->value)  $searchFrom = date('Y-m-d H:i:s', strtotime('-3 months'));
            elseif ($searchMode === Period::Year->value)     $searchFrom = date('Y-m-d H:i:s', strtotime('-1 year'));
            else $searchFrom = '';

            $messages = $this->messageDataHelper->getGroupedMessages($connectedUser->person->Id, $searchFrom);

            $this->render('User/views/messages.latte', $this->getAllParams([
                'messages' => $messages,
                'searchFrom' => $searchFrom,
                'searchMode' => $searchMode,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'person' => $connectedUser->person,
                'page' => $connectedUser->getPage(1),
            ]));
        } else {
            $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
        }
    }


}