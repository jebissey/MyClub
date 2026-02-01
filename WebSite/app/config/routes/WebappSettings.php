<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class WebappSettings implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $webappSettingsController = fn() => $this->controllerFactory->makeWebappSettingsController();

        $this->routes[] = new Route('GET  /settings', $webappSettingsController, 'editSettings');
        $this->routes[] = new Route('POST /settings', $webappSettingsController, 'saveSettings');
        $this->routes[] = new Route('GET  /settings-language', $webappSettingsController, 'saveLanguage');

        return $this->routes;
    }
}
