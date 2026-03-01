<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ApiFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class TranslatorApi implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ApiFactory $apiFactory) {}

    public function get(): array
    {
        $translatorApi = fn() => $this->apiFactory->makeTranslatorApi();

        $this->routes[] = new Route('POST /api/translator/save', $translatorApi, 'save');

        return $this->routes;
    }
}
