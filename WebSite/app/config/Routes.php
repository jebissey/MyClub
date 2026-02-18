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
use app\config\routes\Kanban;
use app\config\routes\KanbanApi;
use app\config\routes\Karaoke;
use app\config\routes\KaraokeApi;
use app\config\routes\Leapfrog;
use app\config\routes\LeapfrogApi;
use app\config\routes\Maintenance;
use app\config\routes\Media;
use app\config\routes\MediaApi;
use app\config\routes\MessageApi;
use app\config\routes\NavBar;
use app\config\routes\NavbarApi;
use app\config\routes\NotificationApi;
use app\config\routes\Order;
use app\config\routes\Person;
use app\config\routes\Registration;
use app\config\routes\Rss;
use app\config\routes\Solfege;
use app\config\routes\Survey;
use app\config\routes\User;
use app\config\routes\UserAccount;
use app\config\routes\UserAvailabilities;
use app\config\routes\UserConnections;
use app\config\routes\UserDashboard;
use app\config\routes\UserDirectory;
use app\config\routes\UserGroups;
use app\config\routes\UserMessages;
use app\config\routes\UserNews;
use app\config\routes\UserNotepad;
use app\config\routes\UserNotifications;
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
use app\helpers\NotificationSender;
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
use app\models\GroupDataHelper;
use app\models\KanbanDataHelper;
use app\models\KaraokeDataHelper;
use app\models\LogDataHelper;
use app\models\LogDataWriterHelper;
use app\models\MessageDataHelper;
use app\models\MetadataDataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\OrderDataHelper;
use app\models\PageDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\SharedFileDataHelper;
use app\models\SurveyDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\DatabaseSmtpConfigProvider;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\EventService;
use app\modules\Common\services\MessageRecipientService;
use app\valueObjects\SmtpConfig;

class Routes
{
    private array $routes;
    private ControllerFactory $controllerFactory;
    private ApiFactory $apiFactory;

    public function __construct(private Application $application, private Engine $flight)
    {
        $dataHelper = new DataHelper($application);
        $smtpProvider = new DatabaseSmtpConfigProvider($dataHelper);

        $authorizationDataHelper = new AuthorizationDataHelper($application);
        $articleDataHelper = new ArticleDataHelper($application, $authorizationDataHelper);
        $crosstabDataHelper = new CrosstabDataHelper($application, $authorizationDataHelper);
        $designDataHelper = new DesignDataHelper($application);
        $emailService = new EmailService($smtpProvider);
        $eventDataHelper = new EventDataHelper($application);
        $groupDataHelper = new GroupDataHelper($application);
        $logDataHelper = new LogDataHelper($application);
        $messageDataHelper = new MessageDataHelper($application);
        $needDataHelper = new NeedDataHelper($application);
        $notificationSender = new NotificationSender($dataHelper);
        $orderDataHelper = new OrderDataHelper($application, $articleDataHelper);
        $participantDataHelper = new ParticipantDataHelper($application);
        $personPreferences = new PersonPreferences($application);
        $personDataHelper = new PersonDataHelper($application, $personPreferences, $emailService);
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
            new AuthenticationService($dataHelper, $emailService),
            new Backup(),
            new CarouselDataHelper($application),
            new CrosstabDataHelper($application, $authorizationDataHelper),
            new DbBrowserDataHelper($application),
            $designDataHelper,
            $emailService,
            new ErrorManager($application),
            $eventDataHelper,
            new EventTypeDataHelper($application),
            new GroupDataHelper($application, $groupDataHelper),
            new KanbanDataHelper($application),
            $logDataHelper,
            $messageDataHelper,
            new MetadataDataHelper($application),
            $needDataHelper,
            new News($newsProviders),
            $notificationSender,
            $orderDataHelper,
            $participantDataHelper,
            $personDataHelper,
            new PersonGroupDataHelper($application),
            new PersonStatisticsDataHelper($application),
            new SharedFileDataHelper($application),
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
            $designDataHelper,
            $emailService,
            $eventDataHelper,
            new EventNeedDataHelper($application),
            new EventService($eventDataHelper),
            new KanbanDataHelper($application),
            new KaraokeDataHelper($application),
            new LogDataWriterHelper($application),
            $messageDataHelper,
            new MessageRecipientService($dataHelper),
            $needDataHelper,
            new NeedTypeDataHelper($application),
            $notificationSender,
            new PageDataHelper($application, $authorizationDataHelper),
            $participantDataHelper,
            $personDataHelper,
            $personPreferences,
            new SharedFileDataHelper($application),
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

        $this->registerStaticRoutes($errorManager);

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
        $this->routes = array_merge($this->routes, (new Kanban($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new KanbanApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Karaoke($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new KaraokeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Leapfrog($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new LeapfrogApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Maintenance($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Media($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new MediaApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new MessageApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new NavBar($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new NavbarApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new NotificationApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Order($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Person($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Registration($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Rss($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Solfege($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Survey($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new User($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserAccount($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserAvailabilities($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserConnections($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserDashboard($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserDirectory($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserGroups($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserMessages($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserNews($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserNotepad($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new UserNotifications($this->controllerFactory))->get());
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

    private function registerStaticRoutes(ErrorManager $errorManager): void
    {
        $rootDir = realpath(__DIR__ . '/../..');

        $staticFiles = [
            '/apple-touch-icon.png' => ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/apple-touch-icon-120x120.png' => ['dir' => 'images', 'file' => 'my-club-120.png', 'type' => 'image/png'],
            '/apple-touch-icon-180x180.png' => ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/apple-touch-icon-precomposed.png' => ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/favicon.ico' => ['dir' => 'images', 'file' => 'favicon.ico', 'type' => 'image/x-icon'],
            '/Feed-icon.svg' => ['dir' => 'images', 'file' => 'Feed-icon.svg', 'type' => 'image/svg+xml'],

            '/robots.txt' => ['dir' => 'root', 'file' => 'robots.txt', 'type' => 'text/plain; charset=UTF-8'],
            '/webCard' => ['dir' => 'root', 'file' => 'businessCard.html', 'type' => 'text/html; charset=UTF-8'],

            '/service-worker.js' => [
                'dir' => 'root',
                'file' => 'service-worker.js',
                'type' => 'application/javascript; charset=UTF-8',
                'noCache' => true,
                'serviceWorker' => true,
            ],

            ...(function () use ($rootDir) {
                $htmlFiles = array_filter(
                    glob($rootDir . '/data/statics/html/*.html') ?: [],
                    'is_file'
                );
                return $htmlFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $htmlFiles),
                    array_map(fn($f) => [
                        'dir' => 'static_html',
                        'file' => basename($f),
                        'type' => 'text/html; charset=UTF-8'
                    ], $htmlFiles)
                ) : [];
            })(),

            ...(function () use ($rootDir) {
                $imageFiles = array_filter(
                    glob($rootDir . '/data/statics/images/*') ?: [],
                    'is_file'
                );
                return $imageFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $imageFiles),
                    array_map(fn($f) => [
                        'dir' => 'static_image',
                        'file' => basename($f),
                        'type' => mime_content_type($f)
                    ], $imageFiles)
                ) : [];
            })(),

            ...(function () use ($rootDir) {
                $cssFiles = array_filter(
                    glob($rootDir . '/data/statics/css/*.css') ?: [],
                    'is_file'
                );
                return $cssFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $cssFiles),
                    array_map(fn($f) => [
                        'dir' => 'static_css',
                        'file' => basename($f),
                        'type' => 'text/css; charset=UTF-8',
                    ], $cssFiles)
                ) : [];
            })(),

            ...(function () {
                $cssDir = __DIR__ . '/../modules/Common/css';
                $cssFiles = array_filter(
                    glob($cssDir . '/*.css') ?: [],
                    'is_file'
                );
                return $cssFiles ? array_combine(
                    array_map(fn($f) => '/public/css/' . basename($f), $cssFiles),
                    array_map(fn($f) => [
                        'dir' => 'css',
                        'file' => $f,
                        'type' => 'text/css; charset=UTF-8',
                        'fullPath' => true
                    ], $cssFiles)
                ) : [];
            })(),

            '/.well-known/appspecific/com.chrome.devtools.json' => ['dir' => null, 'file' => '', 'type' => ''],
        ];

        foreach ($staticFiles as $route => $config) {
            $this->flight->route($route, fn() => $this->serveStaticFile($errorManager, $config));
        }
    }

    private function serveStaticFile(ErrorManager $errorManager, array $config): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $errorManager->raise(
                ApplicationError::MethodNotAllowed,
                'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }
        if (empty($config['file'])) {
            http_response_code(ApplicationError::NoContent->value);
            Flight::stop();
            return;
        }
        $rootDir = realpath(__DIR__ . '/../..');
        if (!empty($config['fullPath'])) {
            $filePath = $config['file'];
        } else {
            $filename = basename($config['file']);
            $baseDir = match ($config['dir']) {
                'images' => $rootDir . '/app/images',
                'static_html' => $rootDir . '/data/statics/html',
                'static_image' => $rootDir . '/data/statics/images',
                'static_css' => $rootDir . '/data/statics/css',
                'root' => $rootDir,
                'css' => __DIR__ . '/../modules/Common/css',
                default => $rootDir,
            };
            $filePath = $baseDir . '/' . $filename;
        }
        if (!file_exists($filePath)) {
            $errorManager->raise(
                ApplicationError::PageNotFound,
                "File $filePath not found in file " . __FILE__ . ' at line ' . __LINE__
            );
            Flight::stop();
            return;
        }
        if (!empty($config['noCache'])) {
            $cacheMaxAge = 0;
        } else {
            $cacheMaxAge = match ($config['dir']) {
                'css' => 31536000,
                'images' => 604800,
                'static_html' => 3600,
                default => 604800,
            };
        }

        $response = Flight::response();
        $response->header('Content-Type', $config['type']);
        $response->header('Content-Length', (string)filesize($filePath));
        $response->header('Last-Modified', gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT');
        $response->header('Cache-Control', "public, max-age=$cacheMaxAge, immutable");
        $response->header('Expires', gmdate('D, d M Y H:i:s', time() + $cacheMaxAge) . ' GMT');
        if (!empty($config['serviceWorker'])) {
            $response->header('Service-Worker-Allowed', '/');
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        readfile($filePath);
        Flight::stop();
    }
}
