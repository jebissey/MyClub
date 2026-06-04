<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class MembershipApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $membershipApi = fn() => $this->apiFactory->makeMembershipApi();

        // Initiated by the member's browser
        $this->routes[] = new Route('POST /api/membership/checkout', $membershipApi, 'checkout');

        // Called by HelloAsso server (no user session)
        $this->routes[] = new Route('POST /api/membership/webhook', $membershipApi, 'webhook');

        // Browser redirect-back from HelloAsso
        $this->routes[] = new Route('GET /api/membership/return', $membershipApi, 'paymentReturn');

        return $this->routes;
    }
}
