<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class WebmasterApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $webmasterApi = fn() => $this->apiFactory->makeWebmasterApi();

        $this->routes[] = new Route('GET  /api/lastVersion', $webmasterApi, 'lastVersion');
        $this->routes[] = new Route('POST /api/notification/send', $webmasterApi, 'sendNotification');
        $this->routes[] = new Route('POST /api/notification/subscribe', $webmasterApi, 'notifySubscribe');
        $this->routes[] = new Route('GET  /api/vapidPublicKey', $webmasterApi, 'getVapidPublicKey');

        return $this->routes;
    }
}
