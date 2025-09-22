<?php

declare(strict_types=1);

namespace app\config;

use flight;
use flight\Engine;

use app\config\routes\Article;
use app\config\routes\ArticleApi;
use app\config\routes\Arward;
use app\config\routes\CarouselApi;
use app\config\routes\Contact;
use app\config\routes\DbBrowser;
use app\config\routes\Design;
use app\config\routes\Designer;
use app\config\routes\Event;
use app\config\routes\EventApi;
use app\config\routes\EventAttributeApi;
use app\config\routes\EventEmail;
use app\config\routes\EventGuest;
use app\config\routes\EventMessageApi;
use app\config\routes\EventNeed;
use app\config\routes\EventNeedApi;
use app\config\routes\EventNeedTypeApi;
use app\config\routes\EventSupplyApi;
use app\config\routes\EventType;
use app\config\routes\Ffa;
use app\config\routes\Group;
use app\config\routes\GroupApi;
use app\config\routes\Home;
use app\config\routes\Import;
use app\config\routes\ImportApi;
use app\config\routes\Log;
use app\config\routes\Maintenance;
use app\config\routes\Media;
use app\config\routes\NavBar;
use app\config\routes\NavbarApi;
use app\config\routes\Person;
use app\config\routes\Registration;
use app\config\routes\Rss;
use app\config\routes\Solfege;
use app\config\routes\Survey;
use app\config\routes\User;
use app\config\routes\UserAccount;
use app\config\routes\UserAvailabilities;
use app\config\routes\UserDashboard;
use app\config\routes\UserDirectory;
use app\config\routes\UserGroups;
use app\config\routes\UserNews;
use app\config\routes\UserNotepad;
use app\config\routes\UserPreferences;
use app\config\routes\UserPresentation;
use app\config\routes\UserStatistics;
use app\config\routes\VisitorInsights;
use app\config\routes\WebappSettings;
use app\config\routes\Webmaster;
use app\config\routes\WebmasterApi;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\News;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\models\DbBrowserDataHelper;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AttributeDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\CarouselDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\EventTypeDataHelper;
use app\models\GenericDataHelper;
use app\models\GroupDataHelper;
use app\models\ImportDataHelper;
use app\models\LogDataHelper;
use app\models\MessageDataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\PageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\SurveyDataHelper;
use app\models\TableControllerDataHelper;
use app\services\AuthenticationService;
use app\services\EmailService;
use app\services\EventService;

class Routes
{
    private array $routes;
    private ControllerFactory $controllerFactory;
    private ApiFactory $apiFactory;

    public function __construct(private Application $application, private Engine $flight)
    {
        $authorizationDataHelper = new AuthorizationDataHelper($application);
        $articleDataHelper = new ArticleDataHelper($application, $authorizationDataHelper);
        $crosstabDataHelper = new CrosstabDataHelper($application, $authorizationDataHelper);
        $dataHelper = new DataHelper($application);
        $designDataHelper = new DesignDataHelper($application);
        $eventDataHelper = new EventDataHelper($application);
        $groupDataHelper = new GroupDataHelper($application);
        $logDataHelper = new LogDataHelper($application);
        $messageDataHelper = new MessageDataHelper($application);
        $needDataHelper = new NeedDataHelper($application);
        $participantDataHelper = new ParticipantDataHelper($application);
        $personPreferences = new PersonPreferences($application);
        $personDataHelper = new PersonDataHelper($application, $personPreferences);
        $surveyDataHelper = new SurveyDataHelper($application, $articleDataHelper);
        $newsProviders = [
            $articleDataHelper,
            $eventDataHelper,
            $messageDataHelper,
            $personDataHelper,
            $surveyDataHelper,
        ];
        $this->controllerFactory = new ControllerFactory(
            $application,
            new ArticleCrosstabDataHelper($application, $crosstabDataHelper),
            $articleDataHelper,
            new ArticleTableDataHelper($application),
            new AuthenticationService($dataHelper),
            new Backup(),
            $application->getConnectedUser(),
            new CrosstabDataHelper($application, $authorizationDataHelper),
            new DataHelper($application),
            new DbBrowserDataHelper($application),
            $designDataHelper,
            new EmailService(),
            new ErrorManager($application),
            $eventDataHelper,
            new EventTypeDataHelper($application),
            new GenericDataHelper($application),
            new GroupDataHelper($application, $groupDataHelper),
            new ImportDataHelper($application),
            $logDataHelper,
            $messageDataHelper,
            $needDataHelper,
            new News($newsProviders),
            $participantDataHelper,
            $personDataHelper,
            new PersonGroupDataHelper($application),
            new PersonStatisticsDataHelper($application),
            $surveyDataHelper,
            new TableControllerDataHelper($application),
            new WebApp(),
        );
        $this->apiFactory = new ApiFactory(
            $application,
            $articleDataHelper,
            new AttributeDataHelper($application),
            $authorizationDataHelper,
            new CarouselDataHelper($application),
            $application->getConnectedUser(),
            $dataHelper,
            $eventDataHelper,
            new EventNeedDataHelper($application),
            new EventService($eventDataHelper),
            $logDataHelper,
            $messageDataHelper,
            $needDataHelper,
            new NeedTypeDataHelper($application),
            $designDataHelper,
            new PageDataHelper($application, $authorizationDataHelper),
            $participantDataHelper,
            $personDataHelper,
            $personPreferences
        );
    }

    public function Add(ErrorManager $errorManager): void
    {
        foreach ($this->get() as $route) {
            $this->mapRoute(
                $this->flight,
                $route->methodAndPath,
                $route->controllerFactory,
                $route->function
            );
        }

        $files = [
            '/apple-touch-icon.png' => ['my-club-180.png', 'image/png'],
            '/apple-touch-icon-120x120.png' => ['my-club-120.png', 'image/png'],
            '/apple-touch-icon-180x180.png' => ['my-club-180.png', 'image/png'],
            '/apple-touch-icon-precomposed.png' => ['my-club-180.png', 'image/png'],
            '/favicon.ico' => ['favicon.ico', 'image/x-icon'],
            '/Feed-icon.svg' => ['Feed-icon.svg', 'image/svg+xml'],
            '/webCard' => ['businessCard.html', 'text/html; charset=UTF-8'],
        ];
        foreach ($files as $route => [$file, $type]) {
            $this->flight->route($route, fn() => $this->serveFile($errorManager, $file, $type));
        }
        $this->flight->route('/*', function () use ($errorManager) {
            $errorManager->raise(ApplicationError::PageNotFound, "Page not found in file " . __FILE__ . ' at line ' . __LINE__);
        });
    }

    #region Private methods
    private function get(): array
    {
        $this->routes = [];

        $this->routes = array_merge($this->routes, (new Article($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new ArticleApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Arward($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new CarouselApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Contact($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new DbBrowser($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Design($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Designer($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Event($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventAttributeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventEmail($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventGuest($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventMessageApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeed($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeedApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeedTypeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventSupplyApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventType($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Ffa($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Group($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new GroupApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Home($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Import($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new ImportApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Log($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Maintenance($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Media($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new NavBar($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new NavbarApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Person($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Registration($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Rss($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Solfege($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Survey($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new User($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserAccount($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserAvailabilities($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserDashboard($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserDirectory($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserGroups($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserNews($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserNotepad($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserPreferences($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserPresentation($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserStatistics($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new VisitorInsights($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new WebappSettings($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Webmaster($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new WebmasterApi($this->apiFactory))->get());

        return $this->routes;
    }

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

    private function serveFile(ErrorManager $errorManager, string $filename, string $contentType = 'image/png'): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $errorManager->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        }
        $filename = basename($filename);
        $path = __DIR__ . "/../images/$filename";
        if (file_exists($path)) {
            $response = Flight::response();
            $response->header('Content-Length', (string)filesize($path));
            $response->header('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT');
            $response->header('Content-Type', $contentType);
            $response->header('Cache-Control', 'public, max-age=604800, immutable');
            $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
            readfile($path);
        } else $errorManager->raise(ApplicationError::PageNotFound, "File $filename not found in file " . __FILE__ . ' at line ' . __LINE__);
        Flight::stop();
    }
}
