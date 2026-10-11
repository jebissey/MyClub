<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

final class Backup implements RouteInterface
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
        $backupController = fn() => $this->controllerFactory->makeBackupController();

        $this->routes[] = new Route('GET /backupAll', $backupController, 'showBackupForm');
        $this->routes[] = new Route('GET /backupAll/download', $backupController, 'download');

        return $this->routes;
    }
}
