<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class GroupApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $groupApi = fn() => $this->apiFactory->makeGroupApi();

        $this->routes[] = new Route('GET  /api/personsInGroup/@id:[0-9]+', $groupApi, 'getPersonsInGroup');
        $this->routes[] = new Route('POST /api/registration/add/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'addToGroup');
        $this->routes[] = new Route('POST /api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'removeFromGroup');

        return $this->routes;
    }
}
