<?php

declare(strict_types=1);

namespace app\config;

use app\config\routes\Article;
use flight\Engine;

use app\config\routes\Arward;
use app\config\routes\Contact;
use app\config\routes\DbBrowser;
use app\config\routes\Design;
use app\config\routes\Designer;
use app\config\routes\Event;
use app\config\routes\EventEmail;
use app\config\routes\EventGuest;
use app\config\routes\EventType;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\models\DbBrowserDataHelper;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\GenericDataHelper;
use app\models\MessageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\TableControllerDataHelper;
use app\services\EmailService;

class Routes
{
    private array $routes = [];
    private ControllerFactory $controllerFactory;

    public function __construct(private Application $application, private Engine $flight)
    {
        $authorizationDataHelper = new AuthorizationDataHelper($application);
        $crosstabDataHelper = new CrosstabDataHelper($application, $authorizationDataHelper);
        $personPreference = new PersonPreferences($application);
        $this->controllerFactory = new ControllerFactory(
            $application,
            new ArticleCrosstabDataHelper($application, $crosstabDataHelper),
            new ArticleDataHelper($application, $authorizationDataHelper),
            new ArticleTableDataHelper($application),
            new Backup(),
            new CrosstabDataHelper($application, $authorizationDataHelper),
            new DbBrowserDataHelper($application),
            new DesignDataHelper($application),
            new EmailService(),
            new ErrorManager($application),
            new EventDataHelper($application),
            new GenericDataHelper($application),
            new MessageDataHelper($application),
            new ParticipantDataHelper($application),
            new PersonDataHelper($application, $personPreference),
            new TableControllerDataHelper($application),
            new WebApp(),
        );
    }

    public function Add(): void
    {
        $this->routes = array_merge($this->routes, (new Article($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Arward($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Contact($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new DbBrowser($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Design($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Designer($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Event($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventEmail($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventGuest($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventType($this->controllerFactory))->get());

        foreach ($this->routes as $route) {
            $this->mapRoute(
                $this->flight,
                $route->methodAndPath,
                $route->controllerFactory,
                $route->function
            );
        }
    }

    #region Private methods
    private function mapRoute(Engine $flight, string $methodAndPath, \Closure $controllerFactory, string $function): void
    {
        preg_match_all('/@(\w+)(?::([^\/]+))?/', $methodAndPath, $matches, PREG_SET_ORDER);
        $paramTypes = [];
        foreach ($matches as $m) {
            $regex = $m[2] ?? null;
            $paramTypes[] = $regex;
        }

        $flight->route($methodAndPath, function (...$args) use ($controllerFactory, $function, $paramTypes) {
            foreach ($args as $i => &$arg) {
                $regex = $paramTypes[$i] ?? null;
                if ($regex === '[0-9]+' && ctype_digit($arg)) {
                    $arg = (int)$arg;
                }
            }
            $controller = $controllerFactory();
            return $controller->$function(...$args);
        });
    }
}
