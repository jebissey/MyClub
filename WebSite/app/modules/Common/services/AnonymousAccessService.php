<?php

declare(strict_types=1);

namespace app\modules\Common\services;

use app\enums\ApplicationError;
use app\helpers\ConnectedUser;
use app\modules\Common\valueObjects\RedirectInfo;
use app\modules\Common\services\AuthenticationService;

/**
 * Anonymous visitor on a protected page: try the "remember me" cookie,
 * otherwise send the visitor to the sign-in page.
 *
 * Shared by every controller that serves protected pages.
 */
final readonly class AnonymousAccessService
{
    public function __construct(
        private AuthenticationService $authenticationService,
        private ConnectedUser $connectedUser,
    ) {
    }

    public function resolve(string $requestUri): RedirectInfo
    {
        $result = $this->authenticationService->handleRememberMeLogin();

        if ($result && $result->isSuccess()) {
            // The cookie login has just changed who is signed in: reload the connected user
            $this->connectedUser->get();

            return new RedirectInfo(
                url: $requestUri,
                status: ApplicationError::Ok,
                message: "Auto sign in succeeded for {$result->getUser()?->email}",
            );
        }

        return new RedirectInfo(url: '/user/sign/in?redirect=' . urlencode($requestUri));
    }
}
