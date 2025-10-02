<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class MediaApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $mediaApi = fn() => $this->apiFactory->makeMediaApi();

        $this->routes[] = new Route('POST /api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename', $mediaApi, 'deleteFile');
        $this->routes[] = new Route('POST /api/media/isShared', $mediaApi, 'isShared');
        $this->routes[] = new Route('POST /api/media/removeShare/@year:[0-9]+/@month:[0-9]+/@filename', $mediaApi, 'removeFileShare');
        $this->routes[] = new Route('POST /api/media/shareFile/@year:[0-9]+/@month:[0-9]+/@filename', $mediaApi, 'shareFile');
        $this->routes[] = new Route('POST /api/media/upload', $mediaApi, 'uploadFile');

        return $this->routes;
    }
}
