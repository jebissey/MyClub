<?php

declare(strict_types=1);

namespace app\modules\Common;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;

abstract class AbstractShowController extends AbstractController
{
    protected function requireConnectedPerson(): ConnectedUser|false
    {
        $connectedUser = $this->application->getConnectedUser();
        if (!($connectedUser->person ?? false)) {
            $this->application->getErrorManager()->raise(
                ApplicationError::Forbidden,
                'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return false;
        }
        return $connectedUser;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveSearchPeriod(ConnectedUser $connectedUser, string $default = ''): array
    {
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
            default                 => $default,
        };

        return [$searchMode, $searchFrom];
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseParams(ConnectedUser $connectedUser, string $parentUrl, string $searchMode, string $searchFrom): array
    {
        return $this->getAllParams([
            'searchFrom'      => $searchFrom,
            'searchMode'      => $searchMode,
            'navItems'        => $this->getNavItems($connectedUser->person),
            'person'          => $connectedUser->person,
            'page'            => $connectedUser->getPage(1),
            'btn_HistoryBack' => true,
            'btn_Parent'      => $parentUrl,
        ]);
    }
}
