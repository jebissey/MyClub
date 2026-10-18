<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class MakeTheTargetApi implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ApiFactory $apiFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $makeTheTargetApi = fn() => $this->apiFactory->makeMakeTheTargetApi();

        $this->routes[] = new Route('POST /api/makethetarget', $makeTheTargetApi, 'logMovement');

        return $this->routes;
    }
}
