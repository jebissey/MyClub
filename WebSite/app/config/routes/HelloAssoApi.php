<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class HelloAssoApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $helloAssoApi = fn() => $this->apiFactory->makeHelloAssoApi();

        // Initiated by the member's browser
        $this->routes[] = new Route('POST /api/helloAsso/checkout', $helloAssoApi, 'checkout');

        // Called by HelloAsso server (no user session)
        $this->routes[] = new Route('POST /api/helloAsso/webhook', $helloAssoApi, 'webhook');

        // Browser redirect-back from HelloAsso
        $this->routes[] = new Route('GET /api/helloAsso/return', $helloAssoApi, 'paymentReturn');

        return $this->routes;
    }
}
