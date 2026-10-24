<?php

declare(strict_types=1);

namespace app\config;

use Closure;
use Flight;
use flight\Engine;
use app\config\routes\Article;
use app\config\routes\ArticleApi;
use app\config\routes\Arward;
use app\config\routes\Astronomy;
use app\config\routes\Backup as BackupAllData;
use app\config\routes\CarouselApi;
use app\config\routes\ChatApi;
use app\config\routes\Communication;
use app\config\routes\CommunicationApi;
use app\config\routes\Contact;
use app\config\routes\DbBrowser;
use app\config\routes\Design;
use app\config\routes\Designer;
use app\config\routes\Event;
use app\config\routes\EventApi;
use app\config\routes\EventAttributeApi;
use app\config\routes\EventAvailabilities;
use app\config\routes\EventAvailabilitiesApi;
use app\config\routes\EventEmail;
use app\config\routes\EventGuest;
use app\config\routes\EventNeed;
use app\config\routes\EventNeedApi;
use app\config\routes\EventNeedTypeApi;
use app\config\routes\EventSupplyApi;
use app\config\routes\EventType;
use app\config\routes\Exercise;
use app\config\routes\ExerciseApi;
use app\config\routes\Export;
use app\config\routes\Ffa;
use app\config\routes\GameManager;
use app\config\routes\Group;
use app\config\routes\GroupApi;
use app\config\routes\HelloAssoApi;
use app\config\routes\Home;
use app\config\routes\Import;
use app\config\routes\ImportApi;
use app\config\routes\Kanban;
use app\config\routes\KanbanApi;
use app\config\routes\Karaoke;
use app\config\routes\KaraokeApi;
use app\config\routes\Leapfrog;
use app\config\routes\LeapfrogApi;
use app\config\routes\Loan;
use app\config\routes\LoanApi;
use app\config\routes\Maintenance;
use app\config\routes\MakeTheTarget;
use app\config\routes\MakeTheTargetApi;
use app\config\routes\Media;
use app\config\routes\MediaApi;
use app\config\routes\MemberFields;
use app\config\routes\Membership;
use app\config\routes\MenuItem;
use app\config\routes\MenuItemApi;
use app\config\routes\MessageApi;
use app\config\routes\NotificationApi;
use app\config\routes\Order;
use app\config\routes\Person;
use app\config\routes\Pwa;
use app\config\routes\Registration;
use app\config\routes\Rss;
use app\config\routes\Solfege;
use app\config\routes\Survey;
use app\config\routes\Translator;
use app\config\routes\TranslatorApi;
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
use app\config\routes\VisitorInsightsApi;
use app\config\routes\WebappSettings;
use app\config\routes\Webmaster;
use app\config\routes\WebmasterApi;
use app\config\valueObjects\DataHelpers;
use app\config\valueObjects\Services;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\GravatarHandler;
use app\helpers\MediaManager;
use app\helpers\MemberCustomFields;
use app\helpers\News;
use app\helpers\NotificationSender;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\models\AlertRecipientDataHelper;
use app\models\DbBrowserDataHelper;
use app\models\ArticleCrosstabDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AttributeDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\AvailabilityDataHelper;
use app\models\CarouselDataHelper;
use app\models\ContactDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\EventTypeDataHelper;
use app\models\ExerciseTableDataHelper;
use app\models\GroupDataHelper;
use app\models\KanbanDataHelper;
use app\models\KaraokeDataHelper;
use app\models\LanguagesDataHelper;
use app\models\LoanDataHelper;
use app\models\LogDataHelper;
use app\models\LogWriterDataHelper;
use app\models\MemberDataHelper;
use app\models\MemberDirectoryDataHelper;
use app\models\MemberImportDataHelper;
use app\models\MembershipDataHelper;
use app\models\MenuItemDataHelper;
use app\models\MessageDataHelper;
use app\models\MetadataDataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\OrderDataHelper;
use app\models\ParticipantDataHelper;
use app\models\PersonDataHelper;
use app\models\PersonGroupDataHelper;
use app\models\PersonStatisticsDataHelper;
use app\models\PresentationNewsDataHelper;
use app\models\SharedFileDataHelper;
use app\models\SurveyDataHelper;
use app\models\TableControllerDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Common\services\ArticleService;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\CredentialService;
use app\modules\Common\services\DatabaseSmtpConfigProvider;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\EventService;
use app\modules\Common\services\JsonEmailQuotaTracker;
use app\modules\Common\services\MemberMapService;
use app\modules\Common\services\MessageRecipientService;
use app\modules\Common\services\RegistrationLinkService;
use app\modules\PersonManager\services\MemberCsvImporter;
use app\modules\PersonManager\services\MemberOdsImporter;
use app\modules\Common\valueObjects\Route;

final class Routes
{
    /**
     * @var array<int, Route>
     */
    private array $routes;
    private ControllerFactory $controllerFactory;
    private ApiFactory $apiFactory;
    private readonly JsonEmailQuotaTracker $quotaTracker;
    private readonly DataHelper $dataHelper;
    private readonly AuthorizationDataHelper $authorizationDataHelper;
    private readonly ArticleAuthorizationService $articleAuthorizationService;
    private readonly PersonPreferences $personPreferences;

    /**
     * Build order, from the objects that depend on nothing to the ones that depend on everything:
     *
     *   1. base objects     only need the application / the generic DataHelper
     *   2. data helpers     may use the base objects (authorization, preferences)
     *   3. services         use the data helpers
     *   4. factories        use data helpers and services
     *
     * ArticleAuthorizationService only depends on AuthorizationDataHelper, so it belongs to layer 1:
     * SurveyDataHelper (layer 2) can use it without any circular dependency with the services (layer 3).
     *
     * @param Engine<object> $flight
     */
    public function __construct(private readonly Application $application, private readonly Engine $flight)
    {
        $this->quotaTracker = new JsonEmailQuotaTracker(dirname(__DIR__, 2) . '/data/email_quota.json');
        $this->dataHelper = new DataHelper(
            $application->getPdo(),
            $application->getErrorManager(),
            $application->getPdoForLog()
        );

        // 1. Base objects
        $this->authorizationDataHelper = new AuthorizationDataHelper($application);
        $this->articleAuthorizationService = new ArticleAuthorizationService($this->authorizationDataHelper);
        $this->personPreferences = new PersonPreferences($this->dataHelper);

        // 2, 3, 4
        $dataHelpers = $this->buildDataHelpers();
        $services = $this->buildServices($dataHelpers);
        $this->buildFactories($dataHelpers, $services);
    }

    public function add(ErrorManager $errorManager): void
    {
        $routes = $this->get();
        $methodsByPath = [];

        foreach ($routes as $route) {
            $this->mapRoute(
                $this->flight,
                $route->methodAndPath,
                $route->controllerFactory,
                $route->function,
                $route->args
            );

            [$methods, $path] = $this->splitMethodAndPath($route->methodAndPath);
            foreach ($methods as $method) {
                $methodsByPath[$path][$method] = true;
            }
        }

        foreach ($methodsByPath as $path => $methods) {
            $allowed = array_keys($methods);
            $this->flight->route(
                'GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS ' . $path,
                function (...$args) use ($errorManager, $allowed) {
                    header('Allow: ' . implode(', ', $allowed));
                    $errorManager->raise(
                        ApplicationError::MethodNotAllowed,
                        'Method ' . WebApp::getRequestMethod() . ' not allowed for this route in file ' . __FILE__ . ' at line ' . __LINE__
                    );
                }
            );
        }

        $this->registerStaticRoutes($errorManager);

        $this->flight->route('/*', function () use ($errorManager) {
            $errorManager->raise(ApplicationError::PageNotFound, "Page not found in file " . __FILE__ . ' at line ' . __LINE__);
        });
    }

    #region Private methods

    /**
     * @return array{0: array<int, string>, 1: string}
     */
    private function splitMethodAndPath(string $methodAndPath): array
    {
        $parts = preg_split('/\s+/', trim($methodAndPath), 2);
        if ($parts === false) {
            $parts = [$methodAndPath];
        }
        $methods = explode('|', $parts[0]);
        return [$methods, $parts[1] ?? '/'];
    }

    /**
     * @return array<int, Route>
     */
    private function get(): array
    {
        $this->routes = [];

        $this->routes = array_merge($this->routes, (new Article($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new ArticleApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Arward($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Astronomy($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new BackupAllData($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new CarouselApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new ChatApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Communication($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new CommunicationApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Contact($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new DbBrowser($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Design($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Designer($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Event($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventAttributeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventAvailabilities($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventAvailabilitiesApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventEmail($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventGuest($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeed($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeedApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventNeedTypeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventSupplyApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new EventType($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Exercise($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new ExerciseApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Export($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Ffa($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new GameManager($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Group($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new GroupApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new HelloAssoApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Home($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Import($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new ImportApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Kanban($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new KanbanApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Karaoke($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new KaraokeApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Leapfrog($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new LeapfrogApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Loan($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new LoanApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new MakeTheTarget($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new MakeTheTargetApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Maintenance($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Media($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new MediaApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new MemberFields($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Membership($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new MenuItem($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new MenuItemApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new MessageApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new NotificationApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new Order($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Person($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Pwa($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Registration($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Rss($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Solfege($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Survey($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Translator($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new TranslatorApi($this->apiFactory))->get());
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
        $this->routes = array_merge($this->routes, (new VisitorInsightsApi($this->apiFactory))->get());
        $this->routes = array_merge($this->routes, (new WebappSettings($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new Webmaster($this->controllerFactory))->get());
        $this->routes = array_merge($this->routes, (new WebmasterApi($this->apiFactory))->get());

        return $this->routes;
    }

    /**
     * @param Engine<object> $flight
     * @param array<int, mixed> $extraArgs
     */
    private function mapRoute(
        Engine $flight,
        string $methodAndPath,
        Closure $controllerFactory,
        string $function,
        array $extraArgs = []
    ): void {
        preg_match_all('/@(\w+)(?::([^\/]+))?/', $methodAndPath, $matches, PREG_SET_ORDER);
        $paramTypes = [];
        foreach ($matches as $m) {
            $paramTypes[] = $m[2] ?? null;
        }

        $flight->route($methodAndPath, function (...$routeArgs) use ($controllerFactory, $function, $paramTypes, $extraArgs) {
            foreach ($routeArgs as $i => &$arg) {
                $regex = $paramTypes[$i] ?? null;
                if ($regex === '[0-9]+' && ctype_digit($arg)) {
                    $arg = (int)$arg;
                }
            }
            $controller = $controllerFactory();
            return $controller->$function(...$extraArgs, ...$routeArgs);
        });
    }

    private function registerStaticRoutes(ErrorManager $errorManager): void
    {
        $rootDir = dirname(__DIR__, 2);

        $staticFiles = [
            // --- Manifest ---
            '/manifest.json' => ['dir' => 'root', 'file' => 'manifest.json', 'type' => 'application/manifest+json; charset=UTF-8'],

            // --- App icons ---
            '/apple-touch-icon.png' =>             ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/apple-touch-icon-120x120.png' =>     ['dir' => 'images', 'file' => 'my-club-120.png', 'type' => 'image/png'],
            '/apple-touch-icon-180x180.png' =>     ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/apple-touch-icon-precomposed.png' => ['dir' => 'images', 'file' => 'my-club-180.png', 'type' => 'image/png'],
            '/favicon.ico' =>                      ['dir' => 'images', 'file' => 'favicon.ico',     'type' => 'image/x-icon'],
            '/Feed-icon.svg' =>                    ['dir' => 'images', 'file' => 'Feed-icon.svg',   'type' => 'image/svg+xml'],
            '/icon-512.png' =>                     ['dir' => 'images', 'file' => 'icon-512.png',    'type' => 'image/png'],

            // --- Root static files ---
            '/robots.txt' => ['dir' => 'root', 'file' => 'robots.txt',        'type' => 'text/plain; charset=UTF-8'],
            '/webCard'    => ['dir' => 'static_html', 'file' => 'businessCard.html', 'type' => 'text/html; charset=UTF-8'],

            // --- Service worker (no cache, scope header required) ---
            '/service-worker.js' => [
                'dir'           => 'root',
                'file'          => 'service-worker.js',
                'type'          => 'application/javascript; charset=UTF-8',
                'noCache'       => true,
                'serviceWorker' => true,
            ],

            // --- Dynamic: HTML files from data/statics/html/ ---
            ...(function () use ($rootDir) {
                $htmlFiles = array_filter(
                    glob($rootDir . '/data/statics/html/*.html') ?: [],
                    'is_file'
                );
                return $htmlFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $htmlFiles),
                    array_map(fn($f) => [
                        'dir'  => 'static_html',
                        'file' => basename($f),
                        'type' => 'text/html; charset=UTF-8',
                    ], $htmlFiles)
                ) : [];
            })(),

            // --- Dynamic: images from data/statics/images/ (MIME auto-detected) ---
            ...(function () use ($rootDir) {
                $imageFiles = array_filter(
                    glob($rootDir . '/data/statics/images/*') ?: [],
                    'is_file'
                );
                return $imageFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $imageFiles),
                    array_map(fn($f) => [
                        'dir'  => 'static_image',
                        'file' => basename($f),
                        'type' => mime_content_type($f) ?: 'application/octet-stream',
                    ], $imageFiles)
                ) : [];
            })(),

            // --- Dynamic: CSS files from data/statics/css/ ---
            ...(function () use ($rootDir) {
                $cssFiles = array_filter(
                    glob($rootDir . '/data/statics/css/*.css') ?: [],
                    'is_file'
                );
                return $cssFiles ? array_combine(
                    array_map(fn($f) => '/' . basename($f), $cssFiles),
                    array_map(fn($f) => [
                        'dir'  => 'static_css',
                        'file' => basename($f),
                        'type' => 'text/css; charset=UTF-8',
                    ], $cssFiles)
                ) : [];
            })(),

            // --- Silently absorb Chrome DevTools probe (avoids 404 noise in logs) ---
            '/.well-known/appspecific/com.chrome.devtools.json' => ['dir' => null, 'file' => '', 'type' => ''],
        ];

        foreach ($staticFiles as $route => $config) {
            $this->flight->route($route, fn() => $this->serveStaticFile($errorManager, $config));
        }
    }

    /**
     * @param array{
     *     dir: string|null,
     *     file: string,
     *     type: string,
     *     noCache?: bool,
     *     serviceWorker?: bool,
     *     fullPath?: bool
     * } $config
     */
    private function serveStaticFile(ErrorManager $errorManager, array $config): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $errorManager->raise(
                ApplicationError::MethodNotAllowed,
                'Method ' . WebApp::getRequestMethod() . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__
            );
            return;
        }
        if (empty($config['file'])) {
            http_response_code(ApplicationError::NoContent->value);
            Flight::stop();
            return;
        }
        $rootDir = dirname(__DIR__, 2);
        if (!empty($config['fullPath'])) {
            $filePath = $config['file'];
        } else {
            $filename = basename($config['file']);
            $baseDir = match ($config['dir']) {
                'images' => $rootDir . '/app/images',
                'sounds' => $rootDir . '/app/sounds',
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

        $mtime = filemtime($filePath);
        $mtime = $mtime !== false ? $mtime : time();
        $response->header('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

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

    /**
     * Layer 2: every data helper is created here, once.
     */
    private function buildDataHelpers(): DataHelpers
    {
        $authorizationDataHelper = $this->authorizationDataHelper;
        $articleDataHelper = new ArticleDataHelper($this->application, $authorizationDataHelper, $this->articleAuthorizationService);
        $crosstabDataHelper = new CrosstabDataHelper($this->application, $authorizationDataHelper);
        $languagesDataHelper = new LanguagesDataHelper($this->application, $this->application->getErrorManager());
        $personDataHelper = new PersonDataHelper($this->application);

        return new DataHelpers(
            alertRecipientDataHelper: new AlertRecipientDataHelper($this->application, $personDataHelper, $this->personPreferences),
            articleCrosstabDataHelper: new ArticleCrosstabDataHelper($this->application, $crosstabDataHelper),
            articleDataHelper: $articleDataHelper,
            articleTableDataHelper: new ArticleTableDataHelper($this->application),
            attributeDataHelper: new AttributeDataHelper($this->application),
            authorizationDataHelper: $authorizationDataHelper,
            availabilityDataHelper: new AvailabilityDataHelper($this->application),
            carouselDataHelper: new CarouselDataHelper($this->application),
            contactDataHelper: new ContactDataHelper($this->application),
            crosstabDataHelper: $crosstabDataHelper,
            dataHelper: $this->dataHelper,
            dbBrowserDataHelper: new DbBrowserDataHelper($this->application),
            designDataHelper: new DesignDataHelper($this->application),
            eventDataHelper: new EventDataHelper($this->application, $this->dataHelper),
            eventNeedDataHelper: new EventNeedDataHelper($this->application),
            eventTypeDataHelper: new EventTypeDataHelper($this->application),
            exerciseTableDataHelper: new ExerciseTableDataHelper($this->application),
            groupDataHelper: new GroupDataHelper($this->application),
            kanbanDataHelper: new KanbanDataHelper($this->application),
            karaokeDataHelper: new KaraokeDataHelper($this->application),
            languagesDataHelper: $languagesDataHelper,
            loanDataHelper: new LoanDataHelper($this->application),
            logDataHelper: new LogDataHelper($this->application, $this->dataHelper),
            logWriterDataHelper: new LogWriterDataHelper($this->application, $this->application->getErrorManager()),
            memberDataHelper: new MemberDataHelper($this->application),
            memberDirectoryDataHelper: new MemberDirectoryDataHelper($this->application),
            memberImportDataHelper: new MemberImportDataHelper($this->application),
            membershipDataHelper: new MembershipDataHelper($this->application),
            menuItemDataHelper: new MenuItemDataHelper($this->application, $authorizationDataHelper),
            messageDataHelper: new MessageDataHelper($this->application, $languagesDataHelper),
            metadataDataHelper: new MetadataDataHelper($this->application),
            needDataHelper: new NeedDataHelper($this->application),
            needTypeDataHelper: new NeedTypeDataHelper($this->application),
            orderDataHelper: new OrderDataHelper($this->application, $articleDataHelper, $this->articleAuthorizationService),
            participantDataHelper: new ParticipantDataHelper($this->application),
            personDataHelper: $personDataHelper,
            personGroupDataHelper: new PersonGroupDataHelper($this->application),
            personStatisticsDataHelper: new PersonStatisticsDataHelper($this->application),
            presentationNewsDataHelper: new PresentationNewsDataHelper($this->application),
            sharedFileDataHelper: new SharedFileDataHelper($this->application),
            surveyDataHelper: new SurveyDataHelper($this->application, $this->articleAuthorizationService),
            tableControllerDataHelper: new TableControllerDataHelper($this->application),
        );
    }

    /**
     * Layer 3: services, built from the data helpers and the base objects.
     */
    private function buildServices(DataHelpers $dataHelpers): Services
    {
        $authenticationService = new AuthenticationService(
            $dataHelpers->memberDataHelper,
            Application::$root
        );
        $this->application->setAuthenticationService($authenticationService);

        $newsProviders = [
            $dataHelpers->articleDataHelper,
            $dataHelpers->eventDataHelper,
            $dataHelpers->messageDataHelper,
            $dataHelpers->presentationNewsDataHelper,
            $dataHelpers->surveyDataHelper,
        ];

        return new Services(
            articleAuthorizationService: $this->articleAuthorizationService,
            authenticationService: $authenticationService,
            emailService: new EmailService(
                new DatabaseSmtpConfigProvider(CredentialService::getInstance()),
                $this->quotaTracker
            ),
            mediaManager: new MediaManager(
                $this->dataHelper,
                $dataHelpers->sharedFileDataHelper,
                $dataHelpers->languagesDataHelper
            ),
            memberCsvImporter: new MemberCsvImporter($dataHelpers->memberImportDataHelper),
            news: new News($newsProviders),
            notificationSender: new NotificationSender($this->dataHelper, CredentialService::getInstance()),
            personDataHelper: $dataHelpers->personDataHelper,
            personPreferences: $this->personPreferences,
        );
    }

    /**
     * Layer 4: no object is created here besides the non-data-helper collaborators.
     */
    private function buildFactories(DataHelpers $dataHelpers, Services $services): void
    {
        $this->controllerFactory = new ControllerFactory(
            $dataHelpers->alertRecipientDataHelper,
            $this->application,
            $services->articleAuthorizationService,
            $dataHelpers->articleCrosstabDataHelper,
            $dataHelpers->articleDataHelper,
            new ArticleService($dataHelpers->carouselDataHelper, $services->mediaManager, $this->dataHelper),
            $dataHelpers->articleTableDataHelper,
            $services->authenticationService,
            new Backup(),
            $dataHelpers->carouselDataHelper,
            $dataHelpers->crosstabDataHelper,
            $dataHelpers->dbBrowserDataHelper,
            $dataHelpers->designDataHelper,
            $services->emailService,
            new ErrorManager($this->application),
            $dataHelpers->eventDataHelper,
            $dataHelpers->eventTypeDataHelper,
            $dataHelpers->exerciseTableDataHelper,
            $dataHelpers->groupDataHelper,
            $dataHelpers->kanbanDataHelper,
            $dataHelpers->loanDataHelper,
            $dataHelpers->logDataHelper,
            new MemberCsvImporter($dataHelpers->memberImportDataHelper),
            new MemberCustomFields($dataHelpers->dataHelper),
            $dataHelpers->memberDataHelper,
            $dataHelpers->memberDirectoryDataHelper,
            new MemberMapService(new GravatarHandler()),
            $dataHelpers->membershipDataHelper,
            $dataHelpers->messageDataHelper,
            $dataHelpers->metadataDataHelper,
            $dataHelpers->needDataHelper,
            $services->news,
            $services->notificationSender,
            $dataHelpers->orderDataHelper,
            $dataHelpers->participantDataHelper,
            $dataHelpers->personDataHelper,
            $dataHelpers->personGroupDataHelper,
            $dataHelpers->personStatisticsDataHelper,
            new RegistrationLinkService($dataHelpers->contactDataHelper, $services->emailService),
            $dataHelpers->sharedFileDataHelper,
            $dataHelpers->surveyDataHelper,
            $dataHelpers->tableControllerDataHelper,
            new WebApp(),
        );

        $this->apiFactory = new ApiFactory(
            $dataHelpers->alertRecipientDataHelper,
            $this->application,
            $services->articleAuthorizationService,
            $dataHelpers->articleDataHelper,
            $dataHelpers->attributeDataHelper,
            $dataHelpers->availabilityDataHelper,
            $dataHelpers->carouselDataHelper,
            $this->application->getConnectedUser(),
            $dataHelpers->designDataHelper,
            $services->emailService,
            $dataHelpers->eventDataHelper,
            $dataHelpers->eventNeedDataHelper,
            new EventService($dataHelpers->eventDataHelper),
            new GravatarHandler(),
            $this->quotaTracker,
            $dataHelpers->kanbanDataHelper,
            $dataHelpers->karaokeDataHelper,
            $dataHelpers->languagesDataHelper,
            $dataHelpers->loanDataHelper,
            $dataHelpers->logDataHelper,
            $dataHelpers->logWriterDataHelper,
            $services->mediaManager,
            new MemberCustomFields($dataHelpers->dataHelper),
            $dataHelpers->memberDataHelper,
            new MemberOdsImporter($dataHelpers->memberImportDataHelper, $dataHelpers->languagesDataHelper),
            $dataHelpers->membershipDataHelper,
            $dataHelpers->menuItemDataHelper,
            $dataHelpers->messageDataHelper,
            new MessageRecipientService($this->dataHelper),
            $dataHelpers->needDataHelper,
            $dataHelpers->needTypeDataHelper,
            $services->notificationSender,
            $dataHelpers->participantDataHelper,
            $dataHelpers->personDataHelper,
        );
    }
}
