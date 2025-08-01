<?php
require_once 'vendor/autoload.php';

use flight\Engine;
use Tracy\Debugger;

use app\apis\ArticleApi;
use app\apis\CarouselApi;
use app\apis\EventApi;
use app\apis\ImportApi;
use app\apis\WebmasterApi;
use app\controllers\ArticleController;
use app\controllers\DbBrowserController;
use app\controllers\DesignController;
use app\controllers\EmailController;
use app\controllers\EventController;
use app\controllers\EventTypeController;
use app\controllers\FFAController;
use app\controllers\GroupController;
use app\controllers\ImportController;
use app\controllers\LogController;
use app\controllers\MediaController;
use app\controllers\NavBarController;
use app\controllers\PersonController;
use app\controllers\RegistrationController;
use app\controllers\SurveyController;
use app\controllers\UserController;
use app\controllers\WebmasterController;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\LogDataHelper;

if ($_SERVER['SERVER_NAME'] === 'localhost')
    Debugger::enable(Debugger::Development, __DIR__ . '/var/tracy/log');
else Debugger::enable(Debugger::Production, __DIR__ . '/var/tracy/log');

$application = Application::init();
$flight = $application->getFlight();

// Add a custom URL parser to fix issue with URL with encoded email address
$flight->map('pass', function ($str) {
    return $str;
});
$flight->before('start', function () {
    session_start();
    if (!isset($_SESSION['token'])) $_SESSION['token'] = bin2hex(random_bytes(32));
});
$flight->map('setData', function ($key, $value) {
    Flight::set($key, $value);
});
$flight->map('getData', function ($key) {
    return Flight::get($key);
});

#region web
$articleController = new ArticleController($application);
mapRoute($flight, 'GET  /articles', $articleController, 'index');
mapRoute($flight, 'GET  /articles/create', $articleController, 'create');
mapRoute($flight, 'GET  /articles/crosstab', $articleController, 'showArticleCrosstab');
mapRoute($flight, 'GET  /articles/delete/@id:[0-9]+', $articleController, 'delete', 1);
mapRoute($flight, 'GET  /articles/@id:[0-9]+', $articleController, 'show', 1);
mapRoute($flight, 'POST /articles/@id:[0-9]+', $articleController, 'update', 1);
mapRoute($flight, 'GET  /publish/article/@id:[0-9]+', $articleController, 'publish', 1);
mapRoute($flight, 'POST /publish/article/@id:[0-9]+', $articleController, 'publish', 1);
mapRoute($flight, 'GET  /redactor', $articleController, 'home');

$dbBrowserController = new DbBrowserController($application);
mapRoute($flight, 'GET  /dbbrowser', $dbBrowserController, 'index');
mapRoute($flight, 'GET  /dbbrowser/@table', $dbBrowserController, 'showTable', 1);
mapRoute($flight, 'GET  /dbbrowser/@table/create', $dbBrowserController, 'showCreateForm', 1);
mapRoute($flight, 'POST /dbbrowser/@table/create', $dbBrowserController, 'createRecord', 1);
mapRoute($flight, 'GET  /dbbrowser/@table/edit/@id:[0-9]+', $dbBrowserController, 'showEditForm', 2);
mapRoute($flight, 'POST /dbbrowser/@table/edit/@id:[0-9]+', $dbBrowserController, 'updateRecord', 2);
mapRoute($flight, 'POST /dbbrowser/@table/delete/@id:[0-9]+', $dbBrowserController, 'deleteRecord', 2);

$designController = new DesignController($application);
mapRoute($flight, 'GET  /designs', $designController, 'index');
mapRoute($flight, 'GET  /designs/create', $designController, 'create');
mapRoute($flight, 'POST /designs/save', $designController, 'save');

$emailController = new EmailController($application);
mapRoute($flight, 'GET  /emails', $emailController, 'fetchEmails');
mapRoute($flight, 'POST /emails', $emailController, 'fetchEmails');
mapRoute($flight, 'GET  /emails/article/@id:[0-9]+', $emailController, 'fetchEmailsForArticle', 1);

$eventController = new EventController($application);
mapRoute($flight, 'GET  /eventManager', $eventController, 'home');
mapRoute($flight, 'GET  /eventManager/help', $eventController, 'help');
mapRoute($flight, 'GET  /nextEvents', $eventController, 'nextEvents');
mapRoute($flight, 'GET  /events/crosstab', $eventController, 'showEventCrosstab');
mapRoute($flight, 'GET  /events/guest', $eventController, 'guest');
mapRoute($flight, 'POST /events/guest', $eventController, 'guestInvite');
mapRoute($flight, 'GET  /events/@id:[0-9]+', $eventController, 'show', 1);
$flight->route('GET  /events/@id/register', function ($id) use ($eventController) {
    $eventController->register($id, true);
});
$flight->route('GET  /events/@id/unregister', function ($id) use ($eventController) {
    $eventController->register($id, false);
});
$flight->route('GET  /events/@id/@token', function ($id, $token) use ($eventController) {
    $eventController->register($id, true, $token);
});
mapRoute($flight, 'GET  /event/location', $eventController, 'location');
mapRoute($flight, 'GET  /needs', $eventController, 'needs');
mapRoute($flight, 'GET  /event/chat/@id:[0-9]+', $eventController, 'showEventChat', 1);
mapRoute($flight, 'GET  /weekEvents', $eventController, 'weekEvents');

$eventTypeController = new EventTypeController($application);
mapRoute($flight, 'GET  /eventTypes', $eventTypeController, 'index');
mapRoute($flight, 'GET  /eventTypes/create', $eventTypeController, 'create');
mapRoute($flight, 'GET  /eventTypes/edit/@id:[0-9]+', $eventTypeController, 'edit', 1);
mapRoute($flight, 'POST /eventTypes/edit/@id:[0-9]+', $eventTypeController, 'edit', 1);
mapRoute($flight, 'GET  /eventTypes/delete/@id:[0-9]+', $eventTypeController, 'delete', 1);

$ffaController = new FFAController($application);
mapRoute($flight, 'GET /ffa/search', $ffaController, 'searchMember');

$groupController = new GroupController($application);
mapRoute($flight, 'GET  /groups', $groupController, 'index');
mapRoute($flight, 'GET  /groups/create', $groupController, 'create');
mapRoute($flight, 'POST /groups/create', $groupController, 'create');
mapRoute($flight, 'GET  /groups/edit/@id:[0-9]+', $groupController, 'edit', 1);
mapRoute($flight, 'POST /groups/edit/@id:[0-9]+', $groupController, 'edit', 1);
mapRoute($flight, 'POST /groups/delete/@id:[0-9]+', $groupController, 'delete', 1);

$importController = new ImportController($application);
mapRoute($flight, 'GET  /import', $importController, 'showImportForm');
mapRoute($flight, 'POST /import', $importController, 'processImport');

$logController = new LogController($application);
mapRoute($flight, 'GET /logs', $logController, 'index');
mapRoute($flight, 'GET /referents', $logController, 'referents');
mapRoute($flight, 'GET /visitors/graf', $logController, 'visitorsGraf');
mapRoute($flight, 'GET /analytics', $logController, 'analytics');
mapRoute($flight, 'GET /topPages', $logController, 'topPagesByPeriod');
mapRoute($flight, 'GET /topArticles', $logController, 'topArticlesByPeriod');
mapRoute($flight, 'GET /crossTab', $logController, 'crossTab');
mapRoute($flight, 'GET /lastVisits', $logController, 'showLastVisits');

$mediaController = new MediaController($application);
mapRoute($flight, 'GET /data/media/@year/@month/@filename', $mediaController, 'viewFile', 3);
mapRoute($flight, 'GET /media/upload', $mediaController, 'showUploadForm');
mapRoute($flight, 'GET /media/list', $mediaController, 'listFiles');
mapRoute($flight, 'GET /media/gpxViewer', $mediaController, 'gpxViewer');

$navBarController = new NavBarController($application);
mapRoute($flight, 'GET  /navBar', $navBarController, 'index');
mapRoute($flight, 'GET  /navBar/show/article/@id:[0-9]+', $navBarController, 'showArticle', 1);
mapRoute($flight, 'GET  /navBar/show/arwards', $navBarController, 'showArwards');

$personController = new PersonController($application);
mapRoute($flight, 'GET  /directory', $personController, 'showDirectory');
mapRoute($flight, 'GET  /members/map', $personController, 'showMap');
mapRoute($flight, 'GET  /personManager', $personController, 'home');
mapRoute($flight, 'GET  /personManager/help', $personController, 'help');
mapRoute($flight, 'GET  /persons', $personController, 'index');
mapRoute($flight, 'GET  /persons/create', $personController, 'create');
mapRoute($flight, 'GET  /persons/edit/@id:[0-9]+', $personController, 'edit', 1);
mapRoute($flight, 'POST /persons/edit/@id:[0-9]+', $personController, 'edit', 1);
mapRoute($flight, 'GET  /persons/delete/@id:[0-9]+', $personController, 'delete', 1);
mapRoute($flight, 'GET  /presentation/edit', $personController, 'editPresentation');
mapRoute($flight, 'POST /presentation/edit', $personController, 'savePresentation');
mapRoute($flight, 'GET  /presentation/@id:[0-9]+', $personController, 'showPresentation', 1);

$registrationController = new RegistrationController($application);
mapRoute($flight, 'GET  /registration', $registrationController, 'index');
mapRoute($flight, 'GET  /registration/groups/@id:[0-9]+', $registrationController, 'getPersonGroups', 1);

$surveyController = new SurveyController($application);
mapRoute($flight, 'GET  /surveys/add/@id', $surveyController, 'add', 1);
mapRoute($flight, 'POST /surveys/create', $surveyController, 'createOrUpdate');
mapRoute($flight, 'GET  /surveys/results/@id:[0-9]+', $surveyController, 'viewResults', 1);

$userController = new UserController($application);
mapRoute($flight, 'GET  /help', $userController, 'helpHome');
mapRoute($flight, 'GET  /', $userController, 'home');
mapRoute($flight, 'GET  /legal/notice', $userController, 'legalNotice');
mapRoute($flight, 'GET  /user', $userController, 'user');
mapRoute($flight, 'GET  /user/account', $userController, 'account');
mapRoute($flight, 'POST /user/account', $userController, 'account');
mapRoute($flight, 'GET  /user/availabilities', $userController, 'availabilities');
mapRoute($flight, 'POST /user/availabilities', $userController, 'availabilities');
mapRoute($flight, 'GET  /user/forgotPassword/@encodedEmail', $userController, 'forgotPassword', 1);
mapRoute($flight, 'GET  /user/groups', $userController, 'groups');
mapRoute($flight, 'POST /user/groups', $userController, 'groups');
mapRoute($flight, 'GET  /user/help', $userController, 'help');
mapRoute($flight, 'GET  /user/news', $userController, 'showNews');
mapRoute($flight, 'GET  /user/preferences', $userController, 'preferences');
mapRoute($flight, 'POST /user/preferences', $userController, 'preferences');
mapRoute($flight, 'GET  /user/setPassword/@token', $userController, 'setPassword', 1);
mapRoute($flight, 'POST /user/setPassword/@token', $userController, 'setPassword', 1);
mapRoute($flight, 'GET  /user/sign/in', $userController, 'signIn');
mapRoute($flight, 'POST /user/sign/in', $userController, 'signIn');
mapRoute($flight, 'GET  /user/sign/out', $userController, 'signOut');
mapRoute($flight, 'GET  /user/statistics', $userController, 'showStatistics');
mapRoute($flight, 'GET  /contact', $userController, 'contact');
mapRoute($flight, 'POST /contact', $userController, 'contact');
mapRoute($flight, 'GET  /contact/event/@id:[0-9]+', $userController, 'contactEvent', 1);

$webmasterController = new WebmasterController($application);
mapRoute($flight, 'GET  /admin', $webmasterController, 'homeAdmin');
mapRoute($flight, 'GET  /admin/help', $webmasterController, 'helpAdmin');
mapRoute($flight, 'GET  /admin/webmaster/help', $webmasterController, 'helpWebmaster');
mapRoute($flight, 'GET  /arwards', $webmasterController, 'arwards');
mapRoute($flight, 'POST /arwards', $webmasterController, 'arwards');
mapRoute($flight, 'GET  /rss.xml', $webmasterController, 'rssGenerator');
mapRoute($flight, 'GET  /sitemap.xml', $webmasterController, 'sitemapGenerator');
mapRoute($flight, 'GET  /webmaster', $webmasterController, 'homeWebmaster');
#endregion

#region api
$articleApi = new ArticleApi($application);
mapRoute($flight, 'GET  /api/author/@articleId', $articleApi, 'getAuthor', 1);
mapRoute($flight, 'POST /api/designs/vote', $articleApi, 'designVote');
mapRoute($flight, 'POST /api/media/delete/@year/@month/@filename', $articleApi, 'deleteFile', 3);
mapRoute($flight, 'POST /api/media/upload', $articleApi, 'uploadFile');
mapRoute($flight, 'POST /api/surveys/reply', $articleApi, 'saveSurveyReply');
mapRoute($flight, 'GET  /api/surveys/reply/@id', $articleApi, 'showSurveyReplyForm', 1);

$carouselApi = new carouselApi($application);
mapRoute($flight, 'GET  /api/carousel/@articleId', $carouselApi, 'getItems', 1);
mapRoute($flight, 'POST /api/carousel/save', $carouselApi, 'saveItem');
mapRoute($flight, 'POST /api/carousel/delete/@id', $carouselApi, 'deleteItem', 1);

// Event API
$eventApi = new EventApi($application);
mapRoute($flight, 'POST   /api/attributes/create', $eventApi, 'createAttribute');
mapRoute($flight, 'DELETE /api/attributes/delete/@id:[0-9]+', $eventApi, 'deleteAttribute', 1);
mapRoute($flight, 'GET    /api/attributes/list', $eventApi, 'getAttributes');
mapRoute($flight, 'POST   /api/attributes/update', $eventApi, 'updateAttribute');
mapRoute($flight, 'GET    /api/attributes-by-event-type/@id:[0-9]+', $eventApi, 'getAttributesByEventType', 1);
mapRoute($flight, 'DELETE /api/event/delete/@id:[0-9]+', $eventApi, 'deleteEvent', 1);
mapRoute($flight, 'POST   /api/event/duplicate/@id:[0-9]+', $eventApi, 'duplicateEvent', 1);
mapRoute($flight, 'POST   /api/event/save', $eventApi, 'saveEvent');
mapRoute($flight, 'POST   /api/event/sendEmails', $eventApi, 'sendEmails');
mapRoute($flight, 'GET    /api/event/@id:[0-9]+', $eventApi, 'getEvent', 1);
mapRoute($flight, 'GET    /api/event-needs/@id:[0-9]+', $eventApi, 'getEventNeeds', 1);
mapRoute($flight, 'POST   /api/event/updateSupply', $eventApi, 'updateSupply');
mapRoute($flight, 'DELETE /api/needs/delete/@id:[0-9]+', $eventApi, 'deleteNeed', 1);
mapRoute($flight, 'POST   /api/needs/save', $eventApi, 'saveNeed');
mapRoute($flight, 'DELETE /api/needs/type/delete/@id:[0-9]+', $eventApi, 'deleteNeedType', 1);
mapRoute($flight, 'POST   /api/needs/type/save', $eventApi, 'saveNeedType');
mapRoute($flight, 'GET    /api/needs-by-need-type/@id:[0-9]+', $eventApi, 'getNeedsByNeedType', 1);
mapRoute($flight, 'POST /api/message/add', $eventApi, 'addMessage');
mapRoute($flight, 'POST /api/message/update', $eventApi, 'updateMessage');
mapRoute($flight, 'POST /api/message/delete', $eventApi, 'deleteMessage');

$importApi = new ImportApi($application);
mapRoute($flight, 'POST /import/headers', $importApi, 'getHeadersFromCSV');

$webmasterApi = new WebmasterApi($application);
mapRoute($flight, 'GET    /api/lastVersion', $webmasterApi, 'lastVersion');
mapRoute($flight, 'DELETE /api/navBar/deleteItem/@id:[0-9]+', $webmasterApi, 'deleteNavbarItem', 1);
mapRoute($flight, 'GET    /api/navBar/getItem/@id:[0-9]+', $webmasterApi, 'getNavbarItem', 1);
mapRoute($flight, 'POST   /api/navBar/saveItem', $webmasterApi, 'saveNavbarItem');
mapRoute($flight, 'POST   /api/navBar/updatePositions', $webmasterApi, 'updateNavbarPositions');
mapRoute($flight, 'GET    /api/personsInGroup/@id:[0-9]+', $webmasterApi, 'getPersonsInGroup', 1);
mapRoute($flight, 'POST   /api/registration/add/@personId:[0-9]+/@groupId:[0-9]+', $webmasterApi, 'addToGroup', 2);
mapRoute($flight, 'POST   /api/registration/remove/@personId:[0-9]+/@groupId:[0-9]+', $webmasterApi, 'removeFromGroup', 2);
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
    $logDataHelper->add(500, 'Internal error: ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line' . $ex->getLine());
    $application->getErrorManager()->raise(ApplicationError::Error, 'Error ' . $ex->getMessage() . ' in file ' . $ex->getFile() . ' at line ' . $ex->getLine());
});
$flight->after('start', function () use ($logDataHelper, $flight) {
    $logDataHelper->add($flight->getData('code'), $flight->getData('message'));
});

$flight->start();

function serveFile($application, $filename, $ContentType = "'Content-Type', 'image/png'"): void
{
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

function mapRoute(Engine $flight, string $methodPath, object $controller, string $methodName, int $paramCount = 0): void {
    $flight->route($methodPath, match ($paramCount) {
        0 => fn()                  => $controller->$methodName(),
        1 => fn($a)                => $controller->$methodName($a),
        2 => fn($a, $b)            => $controller->$methodName($a, $b),
        3 => fn($a, $b, $c)        => $controller->$methodName($a, $b, $c),
        4 => fn($a, $b, $c, $d)    => $controller->$methodName($a, $b, $c, $d),
        default => fn(...$args)    => $controller->$methodName(...$args),
    });
}