<?php
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
use app\modules\Article\ArticleController;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\News;
use app\helpers\PersonPreferences;
use app\models\AuthorizationDataHelper;
use app\models\ArticleDataHelper;
use app\models\ArticleTableDataHelper;
use app\models\AttributeDataHelper;
use app\models\DataHelper;
use app\models\DbBrowserDataHelper;
use app\models\EventDataHelper;
use app\models\EventNeedDataHelper;
use app\models\GroupDataHelper;
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
use app\modules\Article\DesignController;
use app\modules\Article\MediaController;
use app\modules\Article\SurveyController;
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
use app\modules\Webmaster\ArwardsController;
use app\modules\Webmaster\DbBrowserController;
use app\modules\Webmaster\MaintenanceController;
use app\modules\Webmaster\NavBarController;
use app\modules\Webmaster\RssController;
use app\modules\Webmaster\WebappSettingsController;
use app\modules\Webmaster\WebmasterController;
use app\services\AuthenticationService;
use app\services\AuthorizationService;
use app\services\EventService;

if ($_SERVER['SERVER_NAME'] === 'localhost')
    Debugger::enable(Debugger::Development, __DIR__ . '/var/tracy/log');
else Debugger::enable(Debugger::Production, __DIR__ . '/var/tracy/log');

$application = Application::init();
$flight = $application->getFlight();

// Add a custom URL parser to fix issue with URL with encoded email address
$flight->map('pass', function ($str) {
    return $str;
});
$flight->before('start', function () use ($application) {
    session_start();
    if (!isset($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));

    (new ConnectedUser($application))->get();
    (new MaintenanceController($application))->checkIfSiteIsUnderMaintenance();
});
$flight->map('setData', function ($key, $value) {
    Flight::set($key, $value);
});
$flight->map('getData', function ($key) {
    return Flight::get($key);
});

$articleDataHelper = new ArticleDataHelper($application);
$articleTableDataHelper = new ArticleTableDataHelper($application);
$attributeDataHelper = new AttributeDataHelper($application);
$dataHelper = new DataHelper($application);
$authenticationService = new AuthenticationService($dataHelper);
$authorizationDataHelper = new AuthorizationDataHelper($application);
$connectedUser = new ConnectedUser($application);
$dbBrowserDataHelper = new DbBrowserDataHelper($application);
$eventDataHelper = new EventDataHelper($application);
$eventNeedDataHelper = new EventNeedDataHelper($application);
$groupDataHelper = new GroupDataHelper($application);
$needTypeDataHelper = new NeedTypeDataHelper($application);
$messageDataHelper = new MessageDataHelper($application);
$needDataHelper = new NeedDataHelper($application);
$logDataHelper = new LogDataHelper($application);
$pageDataHelper = new PageDataHelper($application);
$participantDataHelper = new ParticipantDataHelper($application);
$personDataHelper = new PersonDataHelper($application);
$personGroupDataHelper = new PersonGroupDataHelper($application);
$personStatisticsDataHelper = new PersonStatisticsDataHelper($application);
$personPreferences = new PersonPreferences();
$surveyDataHelper = new SurveyDataHelper($application);
$news = new News([
    $articleDataHelper,
    $eventDataHelper,
    $messageDataHelper,
    $personDataHelper,
    $surveyDataHelper,
]);

#region web
$articleController = new ArticleController($application, $articleDataHelper, $articleTableDataHelper, $authorizationDataHelper, $personDataHelper);
mapRoute($flight, 'GET  /article/create', $articleController, 'create');
mapRoute($flight, 'POST /article/delete/@id:[0-9]+', $articleController, 'delete');
mapRoute($flight, 'GET  /article/@id:[0-9]+', $articleController, 'show');
mapRoute($flight, 'POST /article/@id:[0-9]+', $articleController, 'update');
mapRoute($flight, 'GET  /articles', $articleController, 'index');
mapRoute($flight, 'GET  /articles/crosstab', $articleController, 'showArticleCrosstab');
mapRoute($flight, 'GET  /emails/article/@id:[0-9]+', $articleController, 'fetchEmailsForArticle');
mapRoute($flight, 'GET  /publish/article/@id:[0-9]+', $articleController, 'publish');
mapRoute($flight, 'POST /publish/article/@id:[0-9]+', $articleController, 'publish');
mapRoute($flight, 'GET  /redactor', $articleController, 'home');
mapRoute($flight, 'GET  /redactor/help', $articleController, 'home');

$arwardsController = new ArwardsController($application);
mapRoute($flight, 'GET  /arwards', $arwardsController, 'seeArwards');
mapRoute($flight, 'POST /arward', $arwardsController, 'setArward');

$contactController = new ContactController($application);
mapRoute($flight, 'GET  /contact', $contactController, 'contact');
mapRoute($flight, 'POST /contact', $contactController, 'contact');
mapRoute($flight, 'GET  /contact/event/@id:[0-9]+', $contactController, 'contact');

$dbBrowserController = new DbBrowserController($application, $dbBrowserDataHelper);
mapRoute($flight, 'GET  /dbbrowser', $dbBrowserController, 'index');
mapRoute($flight, 'GET  /dbbrowser/@table:[A-Za-z0-9_]+', $dbBrowserController, 'showTable', 1);
mapRoute($flight, 'GET  /dbbrowser/@table:[A-Za-z0-9_]+/create', $dbBrowserController, 'showCreateForm');
mapRoute($flight, 'POST /dbbrowser/@table:[A-Za-z0-9_]+/create', $dbBrowserController, 'createRecord');
mapRoute($flight, 'GET  /dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+', $dbBrowserController, 'showEditForm');
mapRoute($flight, 'POST /dbbrowser/@table:[A-Za-z0-9_]+/edit/@id:[0-9]+', $dbBrowserController, 'updateRecord');
mapRoute($flight, 'POST /dbbrowser/@table:[A-Za-z0-9_]+/delete/@id:[0-9]+', $dbBrowserController, 'deleteRecord');

$designController = new DesignController($application);
mapRoute($flight, 'GET  /designs', $designController, 'index');
mapRoute($flight, 'GET  /design/create', $designController, 'create');
mapRoute($flight, 'POST /design/save', $designController, 'save');

$eventController = new EventController($application, $eventDataHelper);
mapRoute($flight, 'GET  /eventManager', $eventController, 'home');
mapRoute($flight, 'GET  /eventManager/help', $eventController, 'help');
mapRoute($flight, 'GET  /event/chat/@id:[0-9]+', $eventController, 'showEventChat');
mapRoute($flight, 'GET  /event/@id:[0-9]+', $eventController, 'show');
$flight->route('GET  /event/@id:[0-9]+/register', function ($id) use ($eventController) {
    $eventController->register($id, true);
});
$flight->route('GET /event/@id:[0-9]+/unregister', function ($id) use ($eventController) {
    $eventController->register($id, false);
});
$flight->route('GET /event/@id:[0-9]+/@token:[a-f0-9]+', function ($id, $token) use ($eventController) {
    $eventController->register($id, true, $token);
});
mapRoute($flight, 'GET  /event/location', $eventController, 'location');
mapRoute($flight, 'GET  /events/crosstab', $eventController, 'showEventCrosstab');
mapRoute($flight, 'GET /nextEvents', $eventController, 'nextEvents');
mapRoute($flight, 'GET  /weekEvents', $eventController, 'weekEvents');

$eventEmailController = new EventEmailController($application);
mapRoute($flight, 'GET  /emails', $eventEmailController, 'fetchEmails');
mapRoute($flight, 'POST /emails', $eventEmailController, 'copyEmails');

$eventGuestController = new EventGuestController($application, $eventDataHelper);
mapRoute($flight, 'GET  /events/guest', $eventGuestController, 'guest');
mapRoute($flight, 'POST /events/guest', $eventGuestController, 'guestInvite');

$eventNeedController = new EventNeedController($application, $needDataHelper);
mapRoute($flight, 'GET /needs', $eventNeedController, 'needs');

$eventTypeController = new EventTypeController($application, $eventDataHelper);
mapRoute($flight, 'GET  /eventTypes', $eventTypeController, 'index');
mapRoute($flight, 'GET  /eventTypes/create', $eventTypeController, 'create');
mapRoute($flight, 'GET  /eventTypes/edit/@id:[0-9]+', $eventTypeController, 'edit');
mapRoute($flight, 'POST /eventTypes/edit/@id:[0-9]+', $eventTypeController, 'editSave');
mapRoute($flight, 'POST /eventTypes/delete/@id:[0-9]+', $eventTypeController, 'delete');

$ffaController = new FFAController($application);
mapRoute($flight, 'GET /ffa/search', $ffaController, 'searchMember');

$groupController = new GroupController($application, $groupDataHelper);
mapRoute($flight, 'GET  /groups', $groupController, 'groupIndex');
mapRoute($flight, 'GET  /group/create', $groupController, 'groupCreate');
mapRoute($flight, 'POST /group/create', $groupController, 'groupCreateSave');
mapRoute($flight, 'GET  /group/edit/@id:[0-9]+', $groupController, 'groupEdit');
mapRoute($flight, 'POST /group/edit/@id:[0-9]+', $groupController, 'groupEditSave');
mapRoute($flight, 'POST /group/delete/@id:[0-9]+', $groupController, 'groupDelete');

$homeController = new HomeController($application);
mapRoute($flight, 'GET  /', $homeController, 'home');
mapRoute($flight, 'GET  /help', $homeController, 'helpHome');
mapRoute($flight, 'GET  /legal/notice', $homeController, 'legalNotice');

$importController = new ImportController($application);
mapRoute($flight, 'GET  /import', $importController, 'showImportForm');
mapRoute($flight, 'POST /import', $importController, 'processImport');

$logController = new LogController($application, $logDataHelper);
mapRoute($flight, 'GET /analytics', $logController, 'analytics');
mapRoute($flight, 'GET /lastVisits', $logController, 'showLastVisits');
mapRoute($flight, 'GET /logs', $logController, 'index');
mapRoute($flight, 'GET /logs/crossTab', $logController, 'crossTab');
mapRoute($flight, 'GET /referents', $logController, 'referents');
mapRoute($flight, 'GET /topArticles', $logController, 'topArticlesByPeriod');
mapRoute($flight, 'GET /topPages', $logController, 'topPagesByPeriod');
mapRoute($flight, 'GET /visitors/graf', $logController, 'visitorsGraf');

$maintenanceController = new MaintenanceController($application);
mapRoute($flight, 'GET /maintenance', $maintenanceController, 'maintenance');
mapRoute($flight, 'GET /maintenance/set', $maintenanceController, 'setSiteUnderMaintenance');
mapRoute($flight, 'GET /maintenance/unset', $maintenanceController, 'setSiteOnline');

$mediaController = new MediaController($application);
mapRoute($flight, 'GET /data/media/@year:[0-9]+/@month:[0-9]+/@filename', $mediaController, 'viewFile');
mapRoute($flight, 'GET /media/upload', $mediaController, 'showUploadForm');
mapRoute($flight, 'GET /media/list', $mediaController, 'listFiles');
mapRoute($flight, 'GET /media/gpxViewer', $mediaController, 'gpxViewer');

$navBarController = new NavBarController($application);
mapRoute($flight, 'GET /navBar', $navBarController, 'index');
mapRoute($flight, 'GET /navBar/show/article/@id:[0-9]+', $navBarController, 'showArticle');
mapRoute($flight, 'GET /navBar/show/arwards', $navBarController, 'showArwards');

$personController = new PersonController($application);
mapRoute($flight, 'GET  /personManager', $personController, 'home');
mapRoute($flight, 'GET  /personManager/help', $personController, 'help');
mapRoute($flight, 'GET  /persons', $personController, 'index');
mapRoute($flight, 'GET  /person/create', $personController, 'create');
mapRoute($flight, 'GET  /person/edit/@id:[0-9]+', $personController, 'edit');
mapRoute($flight, 'POST /person/edit/@id:[0-9]+', $personController, 'editSave');
mapRoute($flight, 'POST /person/delete/@id:[0-9]+', $personController, 'delete');

$registrationController = new RegistrationController($application);
mapRoute($flight, 'GET /registration', $registrationController, 'index');
mapRoute($flight, 'GET /registration/groups/@id:[0-9]+', $registrationController, 'getPersonGroups');

$rssController = new RssController($application);
mapRoute($flight, 'GET /articles-rss.xml', $rssController, 'articlesRssGenerator');
mapRoute($flight, 'GET /events-rss.xml', $rssController, 'eventsRssGenerator');

$surveyController = new SurveyController($application);
mapRoute($flight, 'GET  /survey/add/@id:[0-9]+', $surveyController, 'add');
mapRoute($flight, 'POST /survey/create', $surveyController, 'createOrUpdate');
mapRoute($flight, 'GET  /survey/results/@id:[0-9]+', $surveyController, 'viewResults');

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

$userDashboardController = new UserDashboardController($application);
mapRoute($flight, 'GET  /user', $userDashboardController, 'user');
mapRoute($flight, 'GET  /user/help', $userDashboardController, 'help');

$userDirectoryController = new UserDirectoryController($application);
mapRoute($flight, 'GET  /user/directory', $userDirectoryController, 'showDirectory');
mapRoute($flight, 'GET  /user/directory/map', $userDirectoryController, 'showMap');

$userGroupsController = new UserGroupsController($application, $personGroupDataHelper, $groupDataHelper);
mapRoute($flight, 'GET  /user/groups', $userGroupsController, 'groups');
mapRoute($flight, 'POST /user/groups', $userGroupsController, 'groupsSave');

$userNewsController = new UserNewsController($application, $news);
mapRoute($flight, 'GET  /user/news', $userNewsController, 'showNews');

$userNotepadController = new UserNotepadController($application);
mapRoute($flight, 'GET  /user/notepad', $userNotepadController, 'editNotepad');
mapRoute($flight, 'POST /user/notepad', $userNotepadController, 'saveNotepad');

$userPreferencesController = new UserPreferencesController($application);
mapRoute($flight, 'GET  /user/preferences', $userPreferencesController, 'preferences');
mapRoute($flight, 'POST /user/preferences', $userPreferencesController, 'preferencesSave');

$userPresentationController = new UserPresentationController($application);
mapRoute($flight, 'GET  /user/presentation/edit', $userPresentationController, 'editPresentation');
mapRoute($flight, 'POST /user/presentation/edit', $userPresentationController, 'savePresentation');
mapRoute($flight, 'GET  /user/presentation/@id:[0-9]+', $userPresentationController, 'showPresentation');

$userStatisticsController = new UserStatisticsController($application, $personStatisticsDataHelper, $logDataHelper);
mapRoute($flight, 'GET  /user/statistics', $userStatisticsController, 'showStatistics');

$webappSettingsController = new WebappSettingsController($application);
mapRoute($flight, 'GET  /settings', $webappSettingsController, 'editSettings');
mapRoute($flight, 'POST /settings', $webappSettingsController, 'saveSettings');

$webmasterController = new WebmasterController($application);
mapRoute($flight, 'GET  /admin', $webmasterController, 'homeAdmin');
mapRoute($flight, 'GET  /admin/help', $webmasterController, 'helpAdmin');
mapRoute($flight, 'GET  /admin/webmaster/help', $webmasterController, 'helpWebmaster');
mapRoute($flight, 'GET  /designer', $webmasterController, 'homeDesigner');
mapRoute($flight, 'GET  /designer/help', $webmasterController, 'helpDesigner');
mapRoute($flight, 'GET  /installations', $webmasterController, 'showInstallations');
mapRoute($flight, 'GET  /sitemap.xml', $webmasterController, 'sitemapGenerator');
mapRoute($flight, 'GET  /visitorInsights', $webmasterController, 'visitorInsights');
mapRoute($flight, 'GET  /visitorInsights/help', $webmasterController, 'helpVisitorInsights');
mapRoute($flight, 'GET  /webmaster', $webmasterController, 'homeWebmaster');
#endregion

#region games
$solfegeController = new SolfegeController($application);
mapRoute($flight, 'GET  /games/solfege/learn', $solfegeController, 'learn');
mapRoute($flight, 'POST /games/solfege/save-score', $solfegeController, 'saveScore');
#endrefion

#region api
$articleApi = new ArticleApi($application);
mapRoute($flight, 'GET  /api/author/@articleId:[0-9]+', $articleApi, 'getAuthor');
mapRoute($flight, 'POST /api/design/vote', $articleApi, 'designVote');
mapRoute($flight, 'POST /api/media/delete/@year:[0-9]+/@month:[0-9]+/@filename', $articleApi, 'deleteFile');
mapRoute($flight, 'POST /api/media/upload', $articleApi, 'uploadFile');
mapRoute($flight, 'POST /api/survey/reply', $articleApi, 'saveSurveyReply');
mapRoute($flight, 'GET  /api/survey/reply/@id:[0-9]+', $articleApi, 'showSurveyReplyForm');

$carouselApi = new carouselApi($application);
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
    $messageDataHelper
);
mapRoute($flight, 'GET  /api/attributes-by-event-type/@id:[0-9]+', $eventApi, 'getAttributesByEventType');
mapRoute($flight, 'POST /api/event/delete/@id:[0-9]+', $eventApi, 'deleteEvent');
mapRoute($flight, 'POST /api/event/duplicate/@id:[0-9]+', $eventApi, 'duplicateEvent');
mapRoute($flight, 'POST /api/event/save', $eventApi, 'saveEvent');
mapRoute($flight, 'POST /api/event/sendEmails', $eventApi, 'sendEmails');
mapRoute($flight, 'GET  /api/event/@id:[0-9]+', $eventApi, 'getEvent');

$eventAttributeApi = new EventAttributeApi($application, $attributeDataHelper);
mapRoute($flight, 'POST /api/attribute/create', $eventAttributeApi, 'createAttribute');
mapRoute($flight, 'POST /api/attribute/delete/@id:[0-9]+', $eventAttributeApi, 'deleteAttribute');
mapRoute($flight, 'GET  /api/attributes/list', $eventAttributeApi, 'getAttributes');
mapRoute($flight, 'POST /api/attribute/update', $eventAttributeApi, 'updateAttribute');

$eventMessageApi = new EventMessageApi($application, $messageDataHelper);
mapRoute($flight, 'POST /api/message/add', $eventMessageApi, 'addMessage');
mapRoute($flight, 'POST /api/message/update', $eventMessageApi, 'updateMessage');
mapRoute($flight, 'POST /api/message/delete', $eventMessageApi, 'deleteMessage');

$eventNeedApi = new EventNeedApi($application, $eventNeedDataHelper, $eventDataHelper);
mapRoute($flight, 'GET  /api/event-needs/@id:[0-9]+', $eventNeedApi, 'getEventNeeds');
mapRoute($flight, 'POST /api/need/delete/@id:[0-9]+', $eventNeedApi, 'deleteNeed');
mapRoute($flight, 'POST /api/need/save', $eventNeedApi, 'saveNeed');

$eventNeedTypeApi = new EventNeedTypeApi($application, $needDataHelper, $needTypeDataHelper);
mapRoute($flight, 'POST /api/need/type/delete/@id:[0-9]+', $eventNeedTypeApi, 'deleteNeedType');
mapRoute($flight, 'POST /api/need/type/save', $eventNeedTypeApi, 'saveNeedType');
mapRoute($flight, 'GET  /api/needs-by-need-type/@id:[0-9]+', $eventNeedTypeApi, 'getNeedsByNeedType');

$eventSupplyApi = new EventSupplyApi($application, $eventDataHelper);
mapRoute($flight, 'POST /api/event/updateSupply', $eventSupplyApi, 'updateSupply');

$groupApi = new GroupApi($application);
mapRoute($flight, 'GET  /api/personsInGroup/@id:[0-9]+', $groupApi, 'getPersonsInGroup');
mapRoute($flight, 'POST /api/registration/add/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'addToGroup');
mapRoute($flight, 'POST /api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+', $groupApi, 'removeFromGroup');

$importApi = new ImportApi($application);
mapRoute($flight, 'POST /import/headers', $importApi, 'getHeadersFromCSV');

$navbarApi = new NavbarApi($application, $pageDataHelper);
mapRoute($flight, 'POST /api/navBar/deleteItem/@id:[0-9]+', $navbarApi, 'deleteNavbarItem');
mapRoute($flight, 'GET  /api/navBar/getItem/@id:[0-9]+', $navbarApi, 'getNavbarItem');
mapRoute($flight, 'POST /api/navBar/saveItem', $navbarApi, 'saveNavbarItem');
mapRoute($flight, 'POST /api/navBar/updatePositions', $navbarApi, 'updateNavbarPositions');

$webmasterApi = new WebmasterApi($application);
mapRoute($flight, 'GET    /api/lastVersion', $webmasterApi, 'lastVersion');
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

$flight->route('/webCard', function () use ($application) {
    serveFile($application, 'businessCard.html', "'Content-Type', 'text/html; charset=UTF-8'");
});
$flight->route('/favicon.ico', function () use ($application) {
    serveFile($application, 'favicon.ico');
});
$flight->route('/apple-touch-icon.png', function () use ($application) {
    serveFile($application, 'my-club-180.png');
});
$flight->route('/apple-touch-icon-120x120.png', function () use ($application) {
    serveFile($application, 'my-club-120.png');
});
$flight->route('/apple-touch-icon-180x180.png', function () use ($application) {
    serveFile($application, 'my-club-180.png');
});
$flight->route('/apple-touch-icon-precomposed.png', function () use ($application) {
    serveFile($application, 'my-club-180.png');
});
$flight->route('/*', function () use ($application) {
    $application->getErrorManager()->raise(ApplicationError::PageNotFound, "Page not found in file " . __FILE__ . ' at line ' . __LINE__);
});

$logDataHelper = new LogDataHelper($application);
$flight->map('error', function (Throwable $ex) use ($logDataHelper, $application) {
    $logDataHelper->add(ApplicationError::Error->value, 'Internal error: ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $application->getErrorManager()->raise(ApplicationError::Error, 'Error ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line ' . $ex->getLine());
});
$flight->after('start', function () use ($logDataHelper, $flight) {
    $logDataHelper->add($flight->getData('code') ?? '', $flight->getData('message') ?? '');
});

$flight->start();

function serveFile(Application $application, string $filename, string $ContentType = "'Content-Type', 'image/png'"): void
{
    $filename = basename($filename);
    $path = __DIR__ . "/app/images/$filename";
    if (file_exists($path)) {
        Flight::response()
            ->header($ContentType)
            ->header('Cache-Control', 'public, max-age=604800, immutable')
            ->header('Expires', gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
        readfile($path);
    } else $application->getErrorManager()->raise(ApplicationError::PageNotFound, "File $filename not found in file " . __FILE__ . ' at line ' . __LINE__);
    exit;
}

function mapRoute(Engine $flight, string $methodPath, object $controller, string $methodName): void
{
    $flight->route($methodPath, function (...$args) use ($controller, $methodName) {
        $controller->$methodName(...$args);
    });
}
