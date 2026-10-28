<?php

declare(strict_types=1);

namespace app\modules\Common;

use app\enums\ApplicationError;
use app\enums\Period;
use app\helpers\ConnectedUser;
use app\helpers\To;
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
    protected function resolveSearchPeriod(
        ConnectedUser $connectedUser,
        string $default = '',
        ?string $cookieName = null,
    ): array {
        $allowed = $this->application->enumToValues(Period::class);
        $requested = $this->getQueryString('from', $allowed);

        if ($requested !== null && $cookieName !== null) {
            $this->rememberPeriod($cookieName, $requested);
        }

        $searchMode = $requested
            ?? $this->getRememberedPeriod($cookieName, $allowed)
            ?? Period::Signout->value;

        $searchFrom = match ($searchMode) {
            Period::Signin->value   => $connectedUser->getLastSignIn()  ?? '',
            Period::Signout->value  => $connectedUser->getLastSignOut() ?? '',
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
            'navItems'        => $this->getNavItems($connectedUser->person?->email),
            'person'          => $connectedUser->person,
            'page'            => $connectedUser->getPage(1),
            'btnHistoryBack' => true,
            'btnParent'      => $parentUrl,
        ]);
    }


    /**
     * Stores the period explicitly chosen by the user for one year.
     */
    private function rememberPeriod(string $cookieName, string $mode): void
    {
        setcookie($cookieName, $mode, [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Returns the remembered period, or null if absent or not a known mode.
     *
     * @param string[] $allowed
     */
    private function getRememberedPeriod(?string $cookieName, array $allowed): ?string
    {
        if ($cookieName === null) {
            return null;
        }

        $value = $_COOKIE[$cookieName] ?? null;

        // The cookie is client-controlled: only accept known modes
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }
}
