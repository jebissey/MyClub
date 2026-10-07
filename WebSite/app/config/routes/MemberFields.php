<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class MemberFields implements RouteInterface
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
        $memberFieldController = fn() => $this->controllerFactory->makeMemberFieldController();

        $this->routes[] = new Route('GET /memberFields-settings', $memberFieldController, 'edit');
        $this->routes[] = new Route('POST /memberFields-settings', $memberFieldController, 'save');

        return $this->routes;
    }
}
