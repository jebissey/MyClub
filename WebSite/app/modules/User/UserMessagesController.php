<?php

declare(strict_types=1);

namespace app\modules\User;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\Application;
use app\helpers\GravatarHandler;
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
        if (!($connectedUser->person ?? false)) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }

        $searchMode = WebApp::getFiltered(
            'from',
            $this->application->enumToValues(\app\enums\Period::class),
            $this->flight->request()->query->getData()
        ) ?: Period::Signout->value;

        $searchFrom = match ($searchMode) {
            Period::Signin->value   => $connectedUser->person->LastSignIn  ?? '',
            Period::Signout->value  => $connectedUser->person->LastSignOut ?? '',
            Period::Week->value     => date('Y-m-d H:i:s', strtotime('-1 week')),
            Period::Month->value    => date('Y-m-d H:i:s', strtotime('-1 month')),
            Period::Quarter->value  => date('Y-m-d H:i:s', strtotime('-3 months')),
            Period::Year->value     => date('Y-m-d H:i:s', strtotime('-1 year')),
            default                 => '',
        };

        $this->render('User/views/messages.latte', $this->getAllParams([
            'messages'       => $this->messageDataHelper->getGroupedMessages($connectedUser->person->Id, $searchFrom, new GravatarHandler()),
            'searchFrom'     => $searchFrom,
            'searchMode'     => $searchMode,
            'navItems'       => $this->getNavItems($connectedUser->person),
            'person'         => $connectedUser->person,
            'page'           => $connectedUser->getPage(1),
            'btn_HistoryBack' => true,
            'btn_Parent'     => '/user',
        ]));
    }
}
