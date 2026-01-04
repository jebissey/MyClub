<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class NotificationApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $notificationApi = fn() => $this->apiFactory->makeNotificationApi();

        $this->routes[] = new Route('POST /api/push-subscription', $notificationApi, 'registerPushSubscription');
        $this->routes[] = new Route('POST /api/push-subscription/delete', $notificationApi, 'deletePushSubscription');

        return $this->routes;
    }
}
