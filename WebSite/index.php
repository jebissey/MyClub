<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use flight\Engine;
use Tracy\Debugger;

use app\apis\ArticleApi;
use app\apis\CarouselApi;
use app\apis\EventApi;
use app\apis\EventAttributeApi;
use app\apis\EventMessageApi;
use app\apis\EventNeedApi;
use app\apis\EventNeedTypeApi;
use app\apis\EventSupplyApi;
use app\apis\GroupApi;
use app\apis\ImportApi;
use app\apis\NavbarApi;
use app\apis\WebmasterApi;
use app\config\Routes;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\Backup;
use app\helpers\ErrorManager;
use app\helpers\News;
use app\helpers\PersonPreferences;
use app\helpers\WebApp;
use app\models\ArticleCrosstabDataHelper;
use app\models\AuthorizationDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AttributeDataHelper;
use app\models\CarouselDataHelper;
use app\models\CrosstabDataHelper;
use app\models\DataHelper;
use app\models\DbBrowserDataHelper;
use app\models\DesignDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\EventTypeDataHelper;
use app\models\GenericDataHelper;
use app\models\GroupDataHelper;
use app\models\ImportDataHelper;
use app\models\LanguagesDataHelper;
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
use app\modules\Article\ArticleController;
use app\modules\Article\MediaController;
use app\modules\Article\SurveyController;
use app\modules\Common\EmptyController;
use app\modules\Designer\DesignController;
use app\modules\Designer\DesignerController;
use app\modules\Event\EventController;
use app\modules\Event\EventTypeController;
use app\modules\Event\EventEmailController;
use app\modules\Event\EventGuestController;
use app\modules\Event\EventNeedController;
use app\modules\Games\Solfege\SolfegeController;
use app\modules\PersonManager\GroupController;
use app\modules\PersonManager\ImportController;
use app\modules\PersonManager\PersonController;
use app\modules\PersonManager\RegistrationController;
use app\modules\User\ContactController;
use app\modules\User\FFAController;
use app\modules\User\HomeController;
use app\modules\User\UserController;
use app\modules\User\UserAccountController;
use app\modules\User\UserAvailabilitiesController;
use app\modules\User\UserDashboardController;
use app\modules\User\UserDirectoryController;
use app\modules\User\UserGroupsController;
use app\modules\User\UserNewsController;
use app\modules\User\UserNotepadController;
use app\modules\User\UserPreferencesController;
use app\modules\User\UserPresentationController;
use app\modules\User\UserStatisticsController;
use app\modules\VisitorInsights\LogController;
use app\modules\VisitorInsights\VisitorInsightsController;
use app\modules\Webmaster\ArwardsController;
use app\modules\Webmaster\DbBrowserController;
use app\modules\Webmaster\MaintenanceController;
use app\modules\Webmaster\NavBarController;
use app\modules\Webmaster\RssController;
use app\modules\Webmaster\WebappSettingsController;
use app\modules\Webmaster\WebmasterController;
use app\services\AuthenticationService;
use app\services\AuthorizationService;
use app\services\EmailService;
use app\services\EventService;

$logDir = __DIR__ . '/var/tracy/log';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);
if ($_SERVER['SERVER_NAME'] === 'localhost')
    Debugger::enable(Debugger::Development, $logDir);
else Debugger::enable(Debugger::Production, $logDir);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$application = Application::init();
$flight = $application->getFlight();
// Add a custom URL parser to fix issue with URL with encoded email address
$flight->map('pass', function ($str) {
    return $str;
});



$connectedUser = $application->getConnectedUser();
$errorManager = new ErrorManager($application);
$maintenanceController = new MaintenanceController($application, $errorManager);
$flight->before('start', function () use ($maintenanceController, $connectedUser) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (!isset($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));
    $connectedUser->get();
    $maintenanceController->checkIfSiteIsUnderMaintenance();
});
$flight->map('setData', function ($key, $value) {
    Flight::set($key, $value);
});
$flight->map('getData', function ($key) {
    return Flight::get($key);
});

$authorizationDataHelper = new AuthorizationDataHelper($application);
$dataHelper = new DataHelper($application);
$logDataHelper = new LogDataHelper($application);
$languagesDataHelper = new LanguagesDataHelper($application);
$pageDataHelper = new PageDataHelper($application, $authorizationDataHelper);
$emptyController = new EmptyController($application);
$crosstabDataHelper = new CrosstabDataHelper($application, $authorizationDataHelper);
$articleCrosstabDatahelper = new ArticleCrosstabDataHelper($application, $crosstabDataHelper);
$articleDataHelper = new ArticleDataHelper($application, $authorizationDataHelper);
$articleTableDataHelper = new ArticleTableDataHelper($application);
$attributeDataHelper = new AttributeDataHelper($application);
$backup = new Backup();
$authenticationService = new AuthenticationService($dataHelper);
$carouselDataHelper = new CarouselDataHelper($application);
$dbBrowserDataHelper = new DbBrowserDataHelper($application);
$designDataHelper = new DesignDataHelper($application);
$emailService = new EmailService();
$eventDataHelper = new EventDataHelper($application);
$eventNeedDataHelper = new EventNeedDataHelper($application);
$eventTypeDataHelper = new EventTypeDataHelper($application);
$genericDataHelper = new GenericDataHelper($application);
$groupDataHelper = new GroupDataHelper($application);
$importDataHelper = new ImportDataHelper($application);
$needTypeDataHelper = new NeedTypeDataHelper($application);
$messageDataHelper = new MessageDataHelper($application);
$needDataHelper = new NeedDataHelper($application);
$participantDataHelper = new ParticipantDataHelper($application);
$personPreferences = new PersonPreferences();
$personDataHelper = new PersonDataHelper($application, $personPreferences);
$personGroupDataHelper = new PersonGroupDataHelper($application);
$personStatisticsDataHelper = new PersonStatisticsDataHelper($application);
$surveyDataHelper = new SurveyDataHelper($application, $articleDataHelper);
$news = new News([
    $articleDataHelper,
    $eventDataHelper,
    $messageDataHelper,
    $personDataHelper,
    $surveyDataHelper,
]);
$tableControllerDataHelper = new TableControllerDataHelper($application);
$webapp = new WebApp();


new Routes($application, $flight)->add();


#region web


$eventNeedController = new EventNeedController($application, $needDataHelper);
mapRoute($flight, 'GET /needs', $eventNeedController, 'needs');


$ffaController = new FFAController($application);
mapRoute($flight, 'GET /ffa/search', $ffaController, 'searchMember');

if (str_starts_with($uri, '/group')) {
    $groupController = new GroupController(
        $application,
        $groupDataHelper
    );
    mapRoute($flight, 'GET  /groups', $groupController, 'groupIndex');
    mapRoute($flight, 'GET  /group/create', $groupController, 'groupCreate');
    mapRoute($flight, 'POST /group/create', $groupController, 'groupCreateSave');
    mapRoute($flight, 'GET  /group/edit/@id:[0-9]+', $groupController, 'groupEdit');
    mapRoute($flight, 'POST /group/edit/@id:[0-9]+', $groupController, 'groupEditSave');
    mapRoute($flight, 'POST /group/delete/@id:[0-9]+', $groupController, 'groupDelete');
}

$homeController = new HomeController($application, $articleDataHelper, $surveyDataHelper, $designDataHelper, $news, $personDataHelper);
mapRoute($flight, 'GET  /', $homeController, 'home');
mapRoute($flight, 'GET  /help', $homeController, 'helpHome');
mapRoute($flight, 'GET  /legal/notice', $homeController, 'legalNotice');

if (str_starts_with($uri, '/import')) {
    $importController = new ImportController($application, $importDataHelper);
    mapRoute($flight, 'GET  /import', $importController, 'showImportForm');
    mapRoute($flight, 'POST /import', $importController, 'processImport');

    $importApi = new ImportApi($application, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /import/headers', $importApi, 'getHeadersFromCSV');
}

$logController = new LogController($application, $logDataHelper, $crosstabDataHelper);
mapRoute($flight, 'GET /analytics', $logController, 'analytics');
mapRoute($flight, 'GET /lastVisits', $logController, 'showLastVisits');
mapRoute($flight, 'GET /logs', $logController, 'index');
mapRoute($flight, 'GET /logs/crossTab', $logController, 'crossTab');
mapRoute($flight, 'GET /referents', $logController, 'referents');
mapRoute($flight, 'GET /topArticles', $logController, 'topArticlesByPeriod');
mapRoute($flight, 'GET /topPages', $logController, 'topPagesByPeriod');
mapRoute($flight, 'GET /visitors/graf', $logController, 'visitorsGraf');

mapRoute($flight, 'GET /maintenance', $maintenanceController, 'maintenance');
mapRoute($flight, 'GET /maintenance/set', $maintenanceController, 'setSiteUnderMaintenance');
mapRoute($flight, 'GET /maintenance/unset', $maintenanceController, 'setSiteOnline');

if (str_starts_with($uri, '/media/')) {
    $mediaController = new MediaController($application);
    mapRoute($flight, 'GET /media/@year:[0-9]+/@month:[0-9]+/@filename', $mediaController, 'viewFile');
    mapRoute($flight, 'GET /media/upload', $mediaController, 'showUploadForm');
    mapRoute($flight, 'GET /media/list', $mediaController, 'listFiles');
    mapRoute($flight, 'GET /media/gpxViewer', $mediaController, 'gpxViewer');
}

if (str_starts_with($uri, '/navbar')) {
    $navBarController = new NavBarController($application);
    mapRoute($flight, 'GET /navbar', $navBarController, 'index');
    mapRoute($flight, 'GET /navbar/show/article/@id:[0-9]+', $navBarController, 'showArticle');
    mapRoute($flight, 'GET /navbar/show/arwards', $navBarController, 'showArwards');
}

if (str_starts_with($uri, '/person')) {
    $personController = new PersonController(
        $application,
        $tableControllerDataHelper,
        $personDataHelper,
        $genericDataHelper,
        $dataHelper,
        $languagesDataHelper,
        $pageDataHelper,
        $authorizationDataHelper
    );
    mapRoute($flight, 'GET  /personManager', $personController, 'home');
    mapRoute($flight, 'GET  /personManager/help', $personController, 'help');
    mapRoute($flight, 'GET  /persons', $personController, 'index');
    mapRoute($flight, 'GET  /person/create', $personController, 'create');
    mapRoute($flight, 'GET  /person/edit/@id:[0-9]+', $personController, 'edit');
    mapRoute($flight, 'POST /person/edit/@id:[0-9]+', $personController, 'editSave');
    mapRoute($flight, 'POST /person/delete/@id:[0-9]+', $personController, 'delete');
}

if (str_starts_with($uri, '/registration')) {
    $registrationController = new RegistrationController(
        $application,
        $tableControllerDataHelper,
        $groupDataHelper,
        $genericDataHelper,
    );
    mapRoute($flight, 'GET /registration', $registrationController, 'index');
    mapRoute($flight, 'GET /registration/groups/@id:[0-9]+', $registrationController, 'getPersonGroups');
}

$rssController = new RssController($application, $articleDataHelper, $eventDataHelper);
mapRoute($flight, 'GET /articles-rss.xml', $rssController, 'articlesRssGenerator');
mapRoute($flight, 'GET /events-rss.xml', $rssController, 'eventsRssGenerator');

if (str_starts_with($uri, '/survey')) {
    $surveyController = new SurveyController($application, $surveyDataHelper);
    mapRoute($flight, 'GET  /survey/add/@id:[0-9]+', $surveyController, 'add');
    mapRoute($flight, 'POST /survey/create', $surveyController, 'createOrUpdate');
    mapRoute($flight, 'GET  /survey/results/@id:[0-9]+', $surveyController, 'viewResults');
}

if (str_starts_with($uri, '/user')) {
    $userController = new UserController($application, $authenticationService);
    mapRoute($flight, 'GET  /user/forgotPassword/@encodedEmail', $userController, 'forgotPassword');
    mapRoute($flight, 'GET  /user/setPassword/@token:[a-f0-9]+', $userController, 'setPassword');
    mapRoute($flight, 'POST /user/setPassword/@token:[a-f0-9]+', $userController, 'setPassword');
    mapRoute($flight, 'GET  /user/sign/in', $userController, 'signIn');
    mapRoute($flight, 'POST /user/sign/in', $userController, 'signIn');
    mapRoute($flight, 'GET  /user/sign/out', $userController, 'signOut');

    $userAccountController = new UserAccountController($application);
    mapRoute($flight, 'GET  /user/account', $userAccountController, 'account');
    mapRoute($flight, 'POST /user/account', $userAccountController, 'accountSave');

    $userAvailabilitiesController = new UserAvailabilitiesController($application);
    mapRoute($flight, 'GET  /user/availabilities', $userAvailabilitiesController, 'availabilities');
    mapRoute($flight, 'POST /user/availabilities', $userAvailabilitiesController, 'availabilitiesSave');

    $userDashboardController = new UserDashboardController(
        $application,
        $dataHelper,
        $languagesDataHelper,
        $pageDataHelper,
        $authorizationDataHelper,
        $logDataHelper,
        $emptyController
    );
    mapRoute($flight, 'GET /user', $userDashboardController, 'user');
    mapRoute($flight, 'GET /user/help', $userDashboardController, 'help');

    $userDirectoryController = new UserDirectoryController($application, $personDataHelper, $groupDataHelper);
    mapRoute($flight, 'GET /user/directory', $userDirectoryController, 'showDirectory');
    mapRoute($flight, 'GET /user/directory/map', $userDirectoryController, 'showMap');

    $userGroupsController = new UserGroupsController($application, $personGroupDataHelper, $groupDataHelper);
    mapRoute($flight, 'GET  /user/groups', $userGroupsController, 'groups');
    mapRoute($flight, 'POST /user/groups', $userGroupsController, 'groupsSave');

    $userNewsController = new UserNewsController($application, $news);
    mapRoute($flight, 'GET /user/news', $userNewsController, 'showNews');

    $userNotepadController = new UserNotepadController($application);
    mapRoute($flight, 'GET  /user/notepad', $userNotepadController, 'editNotepad');
    mapRoute($flight, 'POST /user/notepad', $userNotepadController, 'saveNotepad');

    $userPreferencesController = new UserPreferencesController($application, $eventTypeDataHelper);
    mapRoute($flight, 'GET  /user/preferences', $userPreferencesController, 'preferences');
    mapRoute($flight, 'POST /user/preferences', $userPreferencesController, 'preferencesSave');

    $userPresentationController = new UserPresentationController($application);
    mapRoute($flight, 'GET  /user/presentation/edit', $userPresentationController, 'editPresentation');
    mapRoute($flight, 'POST /user/presentation/edit', $userPresentationController, 'savePresentation');
    mapRoute($flight, 'GET  /user/presentation/@id:[0-9]+', $userPresentationController, 'showPresentation');

    $userStatisticsController = new UserStatisticsController($application, $personStatisticsDataHelper, $logDataHelper);
    mapRoute($flight, 'GET /user/statistics', $userStatisticsController, 'showStatistics');
}

if ($uri == '/settings') {
    $webappSettingsController = new WebappSettingsController($application);
    mapRoute($flight, 'GET  /settings', $webappSettingsController, 'editSettings');
    mapRoute($flight, 'POST /settings', $webappSettingsController, 'saveSettings');
}

$visitorInsightsController = new VisitorInsightsController($application);
mapRoute($flight, 'GET  /visitorInsights', $visitorInsightsController, 'visitorInsights');
mapRoute($flight, 'GET  /visitorInsights/help', $visitorInsightsController, 'helpVisitorInsights');

$webmasterController = new WebmasterController($application, $logDataHelper, $articleDataHelper);
mapRoute($flight, 'GET  /admin', $webmasterController, 'homeAdmin');
mapRoute($flight, 'GET  /admin/help', $webmasterController, 'helpAdmin');
mapRoute($flight, 'GET  /admin/webmaster/help', $webmasterController, 'helpWebmaster');
mapRoute($flight, 'GET  /installations', $webmasterController, 'showInstallations');
mapRoute($flight, 'GET  /sitemap.xml', $webmasterController, 'sitemapGenerator');

mapRoute($flight, 'GET  /webmaster', $webmasterController, 'homeWebmaster');
#endregion

#region games
if (str_starts_with($uri, '/games/')) {
    $solfegeController = new SolfegeController($application);
    mapRoute($flight, 'GET  /games/solfege/learn', $solfegeController, 'learn');
    mapRoute($flight, 'POST /games/solfege/save-score', $solfegeController, 'saveScore');
}
#endregion

#region api
if (str_starts_with($uri, '/api/')) {
    $articleApi = new ArticleApi($application, $connectedUser, $dataHelper, $personDataHelper, $designDataHelper, $articleDataHelper);
    mapRoute($flight, 'GET  /api/author/@articleId:[0-9]+', $articleApi, 'getAuthor');
    mapRoute($flight, 'POST /api/design/vote', $articleApi, 'designVote');
    mapRoute($flight, 'POST /api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename', $articleApi, 'deleteFile');
    mapRoute($flight, 'POST /api/media/upload', $articleApi, 'uploadFile');
    mapRoute($flight, 'POST /api/survey/reply', $articleApi, 'saveSurveyReply');
    mapRoute($flight, 'GET  /api/survey/reply/@id:[0-9]+', $articleApi, 'showSurveyReplyForm');

    $carouselApi = new CarouselApi($application, $connectedUser, $dataHelper, $personDataHelper, $authorizationDataHelper, $carouselDataHelper);
    mapRoute($flight, 'GET  /api/carousel/@articleId:[0-9]+', $carouselApi, 'getItems');
    mapRoute($flight, 'POST /api/carousel/save', $carouselApi, 'saveItem');
    mapRoute($flight, 'POST /api/carousel/delete/@id:[0-9]+', $carouselApi, 'deleteItem');

    $eventApi = new EventApi(
        $application,
        new AuthorizationService($connectedUser),
        $eventDataHelper,
        new EventService(
            $eventDataHelper,
            $messageDataHelper,
            $participantDataHelper,
            $personPreferences,
            $personDataHelper
        ),
        $participantDataHelper,
        $personPreferences,
        $messageDataHelper,
        $connectedUser,
        $dataHelper,
        $personDataHelper
    );
    mapRoute($flight, 'GET  /api/event/attributes/eventType/@id:[0-9]+', $eventApi, 'getAttributesByEventType');
    mapRoute($flight, 'POST /api/event/delete/@id:[0-9]+', $eventApi, 'deleteEvent');
    mapRoute($flight, 'POST /api/event/duplicate/@id:[0-9]+', $eventApi, 'duplicateEvent');
    mapRoute($flight, 'POST /api/event/save', $eventApi, 'saveEvent');
    mapRoute($flight, 'POST /api/event/sendEmails', $eventApi, 'sendEmails');
    mapRoute($flight, 'GET  /api/event/@id:[0-9]+', $eventApi, 'getEvent');

    $eventAttributeApi = new EventAttributeApi($application, $attributeDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /api/attribute/create', $eventAttributeApi, 'createAttribute');
    mapRoute($flight, 'POST /api/attribute/delete/@id:[0-9]+', $eventAttributeApi, 'deleteAttribute');
    mapRoute($flight, 'GET  /api/attributes/list', $eventAttributeApi, 'getAttributes');
    mapRoute($flight, 'POST /api/attribute/update', $eventAttributeApi, 'updateAttribute');

    $eventMessageApi = new EventMessageApi($application, $messageDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /api/message/add', $eventMessageApi, 'addMessage');
    mapRoute($flight, 'POST /api/message/update', $eventMessageApi, 'updateMessage');
    mapRoute($flight, 'POST /api/message/delete', $eventMessageApi, 'deleteMessage');

    $eventNeedApi = new EventNeedApi($application, $eventNeedDataHelper, $eventDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'GET  /api/event-needs/@id:[0-9]+', $eventNeedApi, 'getEventNeeds');
    mapRoute($flight, 'POST /api/need/delete/@id:[0-9]+', $eventNeedApi, 'deleteNeed');
    mapRoute($flight, 'POST /api/need/save', $eventNeedApi, 'saveNeed');

    $eventNeedTypeApi = new EventNeedTypeApi($application, $needDataHelper, $needTypeDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /api/need/type/delete/@id:[0-9]+', $eventNeedTypeApi, 'deleteNeedType');
    mapRoute($flight, 'POST /api/need/type/save', $eventNeedTypeApi, 'saveNeedType');
    mapRoute($flight, 'GET  /api/needs-by-need-type/@id:[0-9]+', $eventNeedTypeApi, 'getNeedsByNeedType');

    $eventSupplyApi = new EventSupplyApi($application, $eventDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /api/event/updateSupply', $eventSupplyApi, 'updateSupply');

    $groupApi = new GroupApi($application, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'GET  /api/personsInGroup/@id:[0-9]+', $groupApi, 'getPersonsInGroup');
    mapRoute($flight, 'POST /api/registration/add/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'addToGroup');
    mapRoute($flight, 'POST /api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'removeFromGroup');

    $navbarApi = new NavbarApi($application, $pageDataHelper, $connectedUser, $dataHelper, $personDataHelper);
    mapRoute($flight, 'POST /api/navbar/deleteItem/@id:[0-9]+', $navbarApi, 'deleteNavbarItem');
    mapRoute($flight, 'GET  /api/navbar/getItem/@id:[0-9]+', $navbarApi, 'getNavbarItem');
    mapRoute($flight, 'POST /api/navbar/saveItem', $navbarApi, 'saveNavbarItem');
    mapRoute($flight, 'POST /api/navbar/updatePositions', $navbarApi, 'updateNavbarPositions');

    $webmasterApi = new WebmasterApi($application, $connectedUser, $dataHelper, $personDataHelper, $logDataHelper);
    mapRoute($flight, 'GET /api/lastVersion', $webmasterApi, 'lastVersion');
}
#endregion

$flight->route('/phpInfo', function () {
    header('Content-Type: text/html; charset=UTF-8');
    header('Connection: close');
    ob_start();
    phpinfo();
    $output = ob_get_contents();
    ob_end_clean();
    header('Content-Length: ' . strlen($output));
    echo $output;
    if (ob_get_level()) ob_end_flush();
    flush();
});

$icons = [
    '/favicon.ico' => ['favicon.ico', 'image/x-icon'],
    '/Feed-icon.svg' => ['Feed-icon.svg', 'image/svg+xml'],
    '/apple-touch-icon.png' => ['my-club-180.png', 'image/png'],
    '/apple-touch-icon-120x120.png' => ['my-club-120.png', 'image/png'],
    '/apple-touch-icon-180x180.png' => ['my-club-180.png', 'image/png'],
    '/apple-touch-icon-precomposed.png' => ['my-club-180.png', 'image/png'],
];
foreach ($icons as $route => [$file, $type]) {
    $flight->route($route, fn() => serveFile($errorManager, $file, $type));
}
$flight->route('/webCard', function () use ($errorManager) {
    serveFile($errorManager, 'businessCard.html', 'text/html; charset=UTF-8');
});
$flight->route('/*', function () use ($errorManager) {
    $errorManager->raise(ApplicationError::PageNotFound, "Page not found in file " . __FILE__ . ' at line ' . __LINE__);
});

$flight->map('error', function (Throwable $ex) use ($logDataHelper, $errorManager) {
    $logDataHelper->add((string)ApplicationError::Error->value, 'Internal error: ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $errorManager->raise(ApplicationError::Error, 'Error ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line ' . $ex->getLine());
});
$flight->after('start', function () use ($logDataHelper, $flight) {
    $logDataHelper->add((string)$flight->getData('code') ?? '', $flight->getData('message') ?? '');
});

$flight->start();

#region Private functions
function serveFile(ErrorManager $errorManager, string $filename, string $contentType = 'image/png'): void
{
    $filename = basename($filename);
    $path = __DIR__ . "/app/images/$filename";
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

function mapRoute(Engine $flight, string $methodAndPath, object $controller, string $function): void
{
    preg_match_all('/@(\w+)(?::([^\/]+))?/', $methodAndPath, $matches, PREG_SET_ORDER);
    $paramTypes = [];
    foreach ($matches as $m) {
        $name = $m[1];
        $regex = $m[2] ?? null;
        $paramTypes[] = $regex;
    }
    $flight->route($methodAndPath, function (...$args) use ($controller, $function, $paramTypes) {
        foreach ($args as $i => &$arg) {
            $regex = $paramTypes[$i] ?? null;
            if ($regex === '[0-9]+' && ctype_digit($arg)) $arg = (int)$arg;
        }
        return $controller->$function(...$args);
    });
}
