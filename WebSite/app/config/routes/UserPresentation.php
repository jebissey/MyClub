<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class UserPresentation implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $userPresentationController = fn() => $this->controllerFactory->makeUserPresentationController();

        $this->routes[] = new Route('GET  /user/presentation/edit', $userPresentationController, 'editPresentation');
        $this->routes[] = new Route('POST /user/presentation/edit', $userPresentationController, 'savePresentation');
        $this->routes[] = new Route('GET  /user/presentation/@id:[0-9]+', $userPresentationController, 'showPresentation');

        return $this->routes;
    }
}
