<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Solfege implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $solfegeController = fn() => $this->controllerFactory->makeSolfegeController();

        $this->routes[] = new Route('GET  /games/solfege/learn', $solfegeController, 'learn');
        $this->routes[] = new Route('POST /games/solfege/save-score', $solfegeController, 'saveScore');

        return $this->routes;
    }
}
