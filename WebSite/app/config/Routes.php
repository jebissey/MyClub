<?php

declare(strict_types=1);

namespace app\config;

use Closure;
use Flight;
use flight\Engine;
use app\config\routes\Article;
use app\config\routes\ArticleApi;
use app\config\routes\ArticleList;
use app\config\routes\ArticleStatistics;
use app\config\routes\Award;
use app\config\routes\Astronomy;
use app\config\routes\Backup as BackupAllData;
use app\config\routes\CarouselApi;
use app\config\routes\ChatApi;
use app\config\routes\Communication;
use app\config\routes\CommunicationApi;
use app\config\routes\Contact;
use app\config\routes\Credential;
use app\config\routes\DbBrowser;
use app\config\routes\Design;
use app\config\routes\Designer;
use app\config\routes\EventApi;
use app\config\routes\EventAttributeApi;
use app\config\routes\EventAvailabilities;
use app\config\routes\EventAvailabilitiesApi;
use app\config\routes\EventDetail;
use app\config\routes\EventEmail;
use app\config\routes\EventGuest;
use app\config\routes\EventList;
use app\config\routes\EventManager;
use app\config\routes\EventNeed;
use app\config\routes\EventNeedApi;
use app\config\routes\EventNeedTypeApi;
use app\config\routes\EventRegistration;
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
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\ErrorManager;
use app\helpers\News;
use app\helpers\WebApp;
use app\models\AlertRecipientDataHelper;
use app\models\ArticleDataHelper;
use app\models\DataHelper;
use app\models\EventNewsDataHelper;
use app\models\MemberDataHelper;
use app\models\MessageDataHelper;
use app\models\PresentationNewsDataHelper;
use app\models\SurveyDataHelper;
use app\modules\Article\services\ArticleAlertService;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\services\AuthenticationService;
use app\modules\Common\services\CredentialService;
use app\modules\Common\services\DatabaseSmtpConfigProvider;
use app\modules\Common\services\EmailService;
use app\modules\Common\services\JsonEmailQuotaTracker;
use app\modules\Common\valueObjects\Route;

final class Routes
{
    private readonly ControllerFactory $controllerFactory;
    private readonly ApiFactory $apiFactory;

    public function __construct(private readonly Application $application, private readonly Engine $flight)
    {
        $container = new Container();
        $this->registerDefinitions($container);

        $this->controllerFactory = new ControllerFactory($container);
        $this->apiFactory = new ApiFactory($container);
    }

    public function add(ErrorManager $errorManager): void
    {
        $routes = $this->get();
        $this->flight->set('configuredRoutes', $routes); // used by DesignerController::showRoutes()
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
     * Only what cannot be autowired is declared here: existing instances, singletons,
     * constructors needing scalars, lists or a special setup.
     * Everything else is resolved by the container from the constructor types.
     */
    private function registerDefinitions(Container $container): void
    {
        $application = $this->application;

        // Existing instances
        $container->set(Application::class, $application);
        $container->set(ErrorManager::class, $application->getErrorManager());
        $container->set(ConnectedUser::class, $application->getConnectedUser());
        $container->set(CredentialService::class, CredentialService::getInstance());

        // Constructors needing scalars or a special setup
        $container->define(
            DataHelper::class,
            fn() => new DataHelper(
                $application->getPdo(),
                $application->getErrorManager(),
                $application->getPdoForLog()
            )
        );
        $container->define(
            JsonEmailQuotaTracker::class,
            fn() => new JsonEmailQuotaTracker(dirname(__DIR__, 2) . '/data/email_quota.json')
        );
        $container->define(
            EmailService::class,
            fn(Container $c) => new EmailService(
                new DatabaseSmtpConfigProvider($c->get(CredentialService::class)),
                $c->get(JsonEmailQuotaTracker::class)
            )
        );
        $container->define(
            ArticleAlertService::class,
            fn(Container $c) => new ArticleAlertService(
                $c->get(AlertRecipientDataHelper::class),
                Application::$root
            )
        );
        $container->define(
            AuthenticationService::class,
            function (Container $c) use ($application) {
                $service = new AuthenticationService($c->get(MemberDataHelper::class), Application::$root);
                $application->setAuthenticationService($service);
                return $service;
            }
        );
        $container->define(
            News::class,
            fn(Container $c) => new News([
                $c->get(ArticleDataHelper::class),
                $c->get(EventNewsDataHelper::class),
                $c->get(MessageDataHelper::class),
                $c->get(PresentationNewsDataHelper::class),
                $c->get(SurveyDataHelper::class),
            ])
        );

        // The application expects the authentication service to be registered at startup,
        // so it must not wait for the first lazy resolution.
        $container->get(AuthenticationService::class);
    }

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
        /** @var list<RouteInterface> $providers */
        $providers = [
            new Article($this->controllerFactory),
            new ArticleApi($this->apiFactory),
            new ArticleList($this->controllerFactory),
            new ArticleStatistics($this->controllerFactory),
            new Award($this->controllerFactory),
            new Astronomy($this->controllerFactory),
            new BackupAllData($this->controllerFactory),
            new CarouselApi($this->apiFactory),
            new ChatApi($this->apiFactory),
            new Communication($this->controllerFactory),
            new CommunicationApi($this->apiFactory),
            new Contact($this->controllerFactory),
            new Credential($this->controllerFactory),
            new DbBrowser($this->controllerFactory),
            new Design($this->controllerFactory),
            new Designer($this->controllerFactory),
            new EventApi($this->apiFactory),
            new EventAttributeApi($this->apiFactory),
            new EventAvailabilities($this->controllerFactory),
            new EventAvailabilitiesApi($this->apiFactory),
            new EventDetail($this->controllerFactory),
            new EventEmail($this->controllerFactory),
            new EventGuest($this->controllerFactory),
            new EventList($this->controllerFactory),
            new EventManager($this->controllerFactory),
            new EventNeed($this->controllerFactory),
            new EventNeedApi($this->apiFactory),
            new EventNeedTypeApi($this->apiFactory),
            new EventRegistration($this->controllerFactory),
            new EventSupplyApi($this->apiFactory),
            new EventType($this->controllerFactory),
            new Exercise($this->controllerFactory),
            new ExerciseApi($this->apiFactory),
            new Export($this->controllerFactory),
            new Ffa($this->controllerFactory),
            new GameManager($this->controllerFactory),
            new Group($this->controllerFactory),
            new GroupApi($this->apiFactory),
            new HelloAssoApi($this->apiFactory),
            new Home($this->controllerFactory),
            new Import($this->controllerFactory),
            new ImportApi($this->apiFactory),
            new Kanban($this->controllerFactory),
            new KanbanApi($this->apiFactory),
            new Karaoke($this->controllerFactory),
            new KaraokeApi($this->apiFactory),
            new Leapfrog($this->controllerFactory),
            new LeapfrogApi($this->apiFactory),
            new Loan($this->controllerFactory),
            new LoanApi($this->apiFactory),
            new MakeTheTarget($this->controllerFactory),
            new MakeTheTargetApi($this->apiFactory),
            new Maintenance($this->controllerFactory),
            new Media($this->controllerFactory),
            new MediaApi($this->apiFactory),
            new MemberFields($this->controllerFactory),
            new Membership($this->controllerFactory),
            new MenuItem($this->controllerFactory),
            new MenuItemApi($this->apiFactory),
            new MessageApi($this->apiFactory),
            new NotificationApi($this->apiFactory),
            new Order($this->controllerFactory),
            new Person($this->controllerFactory),
            new Pwa($this->controllerFactory),
            new Registration($this->controllerFactory),
            new Rss($this->controllerFactory),
            new Solfege($this->controllerFactory),
            new Survey($this->controllerFactory),
            new Translator($this->controllerFactory),
            new TranslatorApi($this->apiFactory),
            new User($this->controllerFactory),
            new UserAccount($this->controllerFactory),
            new UserAvailabilities($this->controllerFactory),
            new UserConnections($this->controllerFactory),
            new UserDashboard($this->controllerFactory),
            new UserDirectory($this->controllerFactory),
            new UserGroups($this->controllerFactory),
            new UserMessages($this->controllerFactory),
            new UserNews($this->controllerFactory),
            new UserNotepad($this->controllerFactory),
            new UserNotifications($this->controllerFactory),
            new UserPreferences($this->controllerFactory),
            new UserPresentation($this->controllerFactory),
            new UserStatistics($this->controllerFactory),
            new VisitorInsights($this->controllerFactory),
            new VisitorInsightsApi($this->apiFactory),
            new WebappSettings($this->controllerFactory),
            new Webmaster($this->controllerFactory),
            new WebmasterApi($this->apiFactory),
        ];

        return array_merge(...array_map(static fn(RouteInterface $provider) => $provider->get(), $providers));
    }

    /**
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

    #endregion
}
