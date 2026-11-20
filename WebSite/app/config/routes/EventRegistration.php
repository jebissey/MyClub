<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Event\EventRegistrationController;

final class EventRegistration implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $eventRegistrationController = fn() => $this->controllerFactory->make(EventRegistrationController::class);

        $this->routes[] = new Route('GET  /event/@id:[0-9]+/register', $eventRegistrationController, 'registerSet');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+/unregister', $eventRegistrationController, 'registerUnset');
        $this->routes[] = new Route('GET  /event/@id:[0-9]+/@token:[a-f0-9]+', $eventRegistrationController, 'registerSet');

        return $this->routes;
    }
}
