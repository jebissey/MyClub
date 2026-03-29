<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class CommunicationApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $communicationApi = fn() => $this->apiFactory->makeCommunicationApi();

        $this->routes[] = new Route('GET  /api/communication/quota', $communicationApi, 'getQuota');
        $this->routes[] = new Route('POST /api/communication/members', $communicationApi, 'getMembers');
        $this->routes[] = new Route('POST /api/communication/send', $communicationApi, 'sendCommunication');

        return $this->routes;
    }
}