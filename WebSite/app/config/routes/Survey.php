<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Survey implements RouteInterface
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
        $surveyController = fn() => $this->controllerFactory->makeSurveyController();

        $this->routes[] = new Route('GET  /survey/add/@id:[0-9]+', $surveyController, 'add');
        $this->routes[] = new Route('POST /survey/create', $surveyController, 'createOrUpdate');
        $this->routes[] = new Route('GET  /survey/results/@id:[0-9]+', $surveyController, 'viewResults');

        return $this->routes;
    }
}
