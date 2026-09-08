<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;

class UserNotepad implements RouteInterface
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
        $userNotepadController = fn() => $this->controllerFactory->makeUserNotepadController();

        $this->routes[] = new Route('GET  /user/notepad', $userNotepadController, 'editNotepad');
        $this->routes[] = new Route('POST /user/notepad', $userNotepadController, 'saveNotepad');

        return $this->routes;
    }
}
