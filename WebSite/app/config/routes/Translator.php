<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Translator implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $translatorController = fn() => $this->controllerFactory->makeTranslatorController();

        $this->routes[] = new Route('GET /translator', $translatorController, 'index');

        return $this->routes;
    }
}
