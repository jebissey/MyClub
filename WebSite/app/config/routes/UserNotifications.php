<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class UserNotifications implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $userNotificationsController = fn() => $this->controllerFactory->makeUserNotificationsController();

        $this->routes[] = new Route('GET  /user/notifications', $userNotificationsController, 'notifications');
        $this->routes[] = new Route('POST /user/notifications', $userNotificationsController, 'notificationsSave');

        return $this->routes;
    }
}
