<?php

namespace app\controllers;

use flight\Engine;

use app\helpers\Application;
use app\helpers\AuthorizationDataHelper;
use app\helpers\ArticleDataHelper;
use app\helpers\AttributeDataHelper;
use app\helpers\DesignDataHelper;
use app\helpers\Email;
use app\helpers\EventTypeDataHelper;
use app\helpers\GroupDataHelper;
use app\helpers\LogDataHelper;
use app\helpers\News;
use app\helpers\Params;
use app\helpers\Password;
use app\helpers\PersonGroupDataHelper;
use app\helpers\PersonStatistics;
use app\helpers\SettingsDataHelper;
use app\helpers\Sign;
use app\helpers\SurveyDataHelper;
use app\helpers\TranslationManager;
use app\helpers\Webapp;

class UserController extends BaseController
{
    private ArticleDataHelper $articleDataHelper;
    private AuthorizationDataHelper $authorizationDatahelper;
    private Email $email;
    private SettingsDataHelper $settingsDataHelper;
    private Sign $sign;
    private SurveyDataHelper $surveyDataHelper;

    public function __construct()
    {
        parent::__construct();
        $this->articleDataHelper = new ArticleDataHelper();
        $this->authorizationDatahelper = new AuthorizationDataHelper();
        $this->email = new Email();
        $this->settingsDataHelper = new SettingsDataHelper();
        $this->sign = new Sign($this->dataHelper, $this->personDataHelper, $this->application);
        $this->surveyDataHelper = new SurveyDataHelper();
    }

    #region Sign
    public function forgotPassword($encodedEmail)
    {
        $this->sign->forgotPassword(urldecode($encodedEmail));
    }

    public function setPassword($token)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            $this->sign->checkAndSetPassword($token, $_POST['password']);
        elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('app/views/user/setPassword.latte', [
                'href' => '/user/sign/in',
                'userImg' => '/app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'token' => $token,
                'currentVersion' => Application::getVersion()
            ]);
        } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
            if ($email === '') {
                $this->application->error481($_POST['email'], __FILE__, __LINE__);
            } else {
                $password = $_POST['password'] ?? '';
                if (strlen($password) < 6 || strlen($password) > 30)
                    $this->application->error482('password rules are not respected [6..30] characters', __FILE__, __LINE__);
                else $this->sign->authenticate($email, $password, isset($_POST['rememberMe']) && $_POST['rememberMe'] === 'on');
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_COOKIE['rememberMe'])) {
                $token = $_COOKIE['rememberMe'];
                $person = $this->dataHelper->get('Person', ['Token' => $token]);
                if ($person && $person->Inactivated == 0) {
                    $this->dataHelper->set('Person', ['LastSignIn' => date('Y-m-d H:i:s')], ['Id' => $person->Id]);
                    $_SESSION['user'] = $person->Email;
                    $_SESSION['navbar'] = '';
                } else setcookie('rememberMe', '', time() - 3600, '/');
                $this->flight->redirect('/');
                return;
            }

            $this->render('app/views/user/signIn.latte', [
                'href' => '/user/sign/in',
                'userImg' => '/app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'currentVersion' => Application::getVersion()
            ]);
        } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
    }

    public function signOut()
    {
        (new LogDataHelper())->add(200, 'Sign out succeeded with with ' . $_SESSION['user'] ?? '');
        $this->dataHelper->set('Person',  ['LastSignOut' => date('Y-m-d H:i:s')], ['Email COLLATE NOCASE' . $_SESSION['user'] => null]);
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
        $this->flight->redirect('/');
    }
    #endregion

    public function helpHome(): void
    {
        $content = $this->application->getLatte()->latte->renderToString('app/views/info.latte', [
            'content' => $this->settingsDataHelper->get_('Help_home'),
            'hasAuthorization' => $this->authorizationDatahelper->hasAutorization(),
            'currentVersion' => $this->application->getVersion()
        ]);
        echo $content;
    }

    public function legalNotice(): void
    {
        $content = $this->application->getLatte()->latte->renderToString('app/views/info.latte', [
            'content' => $this->settingsDataHelper->get_('LegalNotices'),
            'hasAuthorization' => $this->authorizationDatahelper->hasAutorization(),
            'currentVersion' => $this->application->getVersion()
        ]);
        echo $content;
    }

    public function home()
    {
        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            $person = $this->personDataHelper->getPerson();
            if (!$person) {
                unset($_SESSION['user']);
                $this->application->error480($userEmail, __FILE__, __LINE__);
            }
            $pendingSurveyResponses = $this->surveyDataHelper->getPendingSurveyResponses();
            $userPendingSurveys = array_filter($pendingSurveyResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            $pendingDesignResponses = (new DesignDataHelper())->getPendingDesignResponses();
            $userPendingDesigns = array_filter($pendingDesignResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });

            $news = (new News())->anyNews($person);
        } else {
            $lang = TranslationManager::getCurrentLanguage();
            Params::setParams([
                'href' => '/user/sign/in',
                'userImg' => '/app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'currentVersion' => Application::getVersion(),
                'currentLanguage' => $lang,
                'supportedLanguages' => TranslationManager::getSupportedLanguages(),
                'flag' => TranslationManager::getFlag($lang),
                'isRedactor' => false,
            ]);
        }
        $articles = $this->articleDataHelper->getLatestArticles($userEmail);
        $latestArticle = $articles['latestArticle'];
        $spotlight = $this->articleDataHelper->getSpotlightArticle();
        if ($spotlight !== null) {
            $articleId = $spotlight['articleId'];
            if ($this->articleDataHelper->isUserAllowedToReadArticle($userEmail, $articleId)) {
                $spotlightUntil = $spotlight['spotlightUntil'];
                if (strtotime($spotlightUntil) >= strtotime(date('Y-m-d')))
                    $latestArticle = $this->articleDataHelper->getWithAuthor($articleId);
            }
        }

        $this->render('app/views/home.latte', $this->params->getAll([
            'latestArticle' => $latestArticle,
            'latestArticles' => $articles['latestArticles'],
            'greatings' => $this->settingsDataHelper->get_('Greatings'),
            'link' => $this->settingsDataHelper->get_('Link'),
            'navItems' => $this->getNavItems($person),
            'publishedBy' => $articles['latestArticle']
                && $articles['latestArticle']->PublishedBy != $articles['latestArticle']->CreatedBy ? $this->personDataHelper->getPublisher($articles['latestArticle']->PublishedBy) : '',
            'latestArticleHasSurvey' => $this->surveyDataHelper->articleHasSurvey($articles['latestArticle']->Id ?? 0),
            'pendingSurveys' => $userPendingSurveys,
            'pendingDesigns' => $userPendingDesigns,
            'news' => $news ?? false,
        ]));
    }

    #region Data user
    public function user()
    {
        if ($this->personDataHelper->getPerson()) {
            $_SESSION['navbar'] = 'user';
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/user.latte', $this->params->getAll([]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function account()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                $password = $_POST['password'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $nickName = $_POST['nickName'];
                $avatar = pathinfo($_POST['avatar'], PATHINFO_BASENAME) ?? '';
                $useGravatar = $_POST['useGravatar'] ?? 'no';
                $this->dataHelper->set('Person', [
                    'FirstName' => $firstName,
                    'LastName' => $lastName,
                    'NickName' => $nickName,
                    'Avatar' => $avatar,
                    'useGravatar' => $useGravatar
                ], ['Id' => $person->Id]);
                if (!empty($password))
                    $this->dataHelper->set('Person', ['Password' => Password::signPassword($password)], ['Id' => $person->Id]);
                if ($person->Imported == 0) {
                    $this->dataHelper->set('Person', ['Email' => $email], ['Id' => $person->Id]);
                    $_SESSION['user'] = $email;
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $email = filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?? '';
                $firstName = WebApp::sanitizeInput($person->FirstName);
                $lastName = WebApp::sanitizeInput($person->LastName);
                $nickName = WebApp::sanitizeInput($person->NickName);
                $avatar = WebApp::sanitizeInput($person->Avatar);
                $useGravatar = WebApp::sanitizeInput($person->UseGravatar) ?? 'no';
                $emojiFiles = glob(__DIR__ . '/../images/emoji*');
                $emojis = array_map(function ($path) {
                    return basename($path);
                }, $emojiFiles);

                $this->render('app/views/user/account.latte', $this->params->getAll([
                    'readOnly' => $person->Imported == 1 ? true : false,
                    'email' => $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'nickName' => $nickName,
                    'avatar' => $avatar,
                    'useGravatar' => $useGravatar,
                    'emojis' => $emojis,
                    'emojiPath' => '/app/images/',
                    'isSelfEdit' => true,
                    'layout' => Webapp::getLayout()()
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function availabilities()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $availabilities = $_POST['availabilities'] ?? '';
                if ($availabilities != '') $this->dataHelper->set('Person', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentAvailabilities = json_decode($person->Availabilities ?? '', true);
                $this->render('app/views/user/availabilities.latte', $this->params->getAll(['currentAvailabilities' => $currentAvailabilities]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function preferences()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = $_POST['preferences'];
                $this->dataHelper->set('Person', ['preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $eventTypes = (new EventTypeDataHelper())->getsFor($person->Id);
                $eventTypesWithAttributes = [];
                $attributeDataHelper = new AttributeDataHelper();
                foreach ($eventTypes as $eventType) {
                    $eventType->Attributes = $attributeDataHelper->getAttributesOf($eventType->Id);
                    $eventTypesWithAttributes[] = $eventType;
                }

                $this->render('app/views/user/preferences.latte', $this->params->getAll([
                    'currentPreferences' => json_decode($person->Preferences ?? '', true),
                    'eventTypes' => $eventTypesWithAttributes
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function groups()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                (new PersonGroupDataHelper())->update($person->Id, $_POST['groups'] ?? []);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentGroups = (new GroupDataHelper())->getCurrentGroups($person->Id);

                $this->render('app/views/user/groups.latte', $this->params->getAll([
                    'groups' => $currentGroups,
                    'layout' => Webapp::getLayout()()
                ]));
            } else $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }
    #endregion 

    public function help()
    {
        if ($this->personDataHelper->getPerson()) {
            $this->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settingsDataHelper->get_('Help_user'),
                'hasAuthorization' => $this->authorizationDatahelper->hasAutorization(),
                'currentVersion' => Application::getVersion()
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    public function contact($eventId = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $person = $this->personDataHelper->getPerson();
            $this->render('app/views/contact.latte', $this->params->getAll([
                'navItems' => $this->getNavItems($person),
                'event' => $eventId != null ? $this->dataHelper->get('Event', ['Id' => $eventId]) : null,
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Le nom et prénom sont requis.';
            }
            if (empty($email)) $errors[] = 'L\'email est requis.';
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'L\'email n\'est pas valide.';
            if (empty($message)) $errors[] = 'Le message est requis.';
            if (empty($errors)) {
                $adminEmail = $this->settingsDataHelper->get_('contactEmail');
                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->application->error500('Invalid contactEmmail', __FILE__, __LINE__);
                    return;
                }
                $eventId = trim($_POST['eventId'] ?? '');
                $event = $this->dataHelper->get('Event', ['Id' => $eventId]);
                if (!$event) {
                    $this->application->error471($eventId, __FILE__, __LINE__);
                    return false;
                }
                if (!empty($eventId)) $emailSent = $this->email->sendRegistrationLink($adminEmail, $name, $email, $event);
                else $emailSent = $this->email->sendContactEmail($adminEmail, $name, $email, $message);
                if ($emailSent) {
                    $url = (new Webapp())->buildUrl('/contact', [
                        'success' => 'Message envoyé avec succès.',
                        'who'     => $email
                    ]);
                    $this->flight->redirect($url);
                } else {
                    $params = [
                        'error' => 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.',
                        'old_name' => $name,
                        'old_email' => $email,
                        'old_message' => $message
                    ];
                    $queryString = http_build_query($params);
                    $this->flight->redirect('/contact?' . $queryString);
                }
            } else {
                $params = [
                    'errors' => implode('|', $errors),
                    'old_name' => $name,
                    'old_email' => $email,
                    'old_message' => $message
                ];
                $queryString = http_build_query($params);
                $this->flight->redirect('/contact?' . $queryString);
            }
        }
    }

    #region News
    public function showNews()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {
            $searchMode = $_GET['from'] ?? 'signout';
            if ($searchMode === 'signin') {
                $searchFrom = $person->LastSignIn ?? '';
            } elseif ($searchMode === 'signout') {
                $searchFrom = $person->LastSignOut ?? '';
            } elseif ($searchMode === 'week') {
                $searchFrom = date('Y-m-d H:i:s', strtotime('-1 week'));
            } elseif ($searchMode === 'month') {
                $searchFrom = date('Y-m-d H:i:s', strtotime('-1 month'));
            }

            $this->render('app/views/user/news.latte', $this->params->getAll([
                'news' => (new News())->getNewsForPerson($person, $searchFrom),
                'searchFrom' => $searchFrom,
                'searchMode' => $searchMode,
                'navItems' => $this->getNavItems($person),
                'person' => $person
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }

    #region Statistics
    public function showStatistics()
    {
        if ($person = $this->personDataHelper->getPerson([], 1)) {
            $personalStatistics = new PersonStatistics();
            $season = $personalStatistics->getSeasonRange();
            $this->render('app/views/user/statistics.latte', $this->params->getAll([
                'stats' => $personalStatistics->getStats($person, $season['start'], $season['end'], $this->authorizationDatahelper->isWebmaster()),
                'seasons' => $personalStatistics->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems($person),
                'chartData' => $this->getVisitStatsForChart($season, $person),
            ]));
        } else $this->application->error403(__FILE__, __LINE__);
    }
    private function getVisitStatsForChart($season, $person)
    {
        $stats = $this->getVisitStats($season);
        $currentUserTranche = $this->getCurrentUserTranche($stats, $person);

        $chartData = [];
        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $chartData[] = [
                'tranche' => $stats['tranches'][$i]['label'],
                'count' => $stats['distribution'][$i],
                'isCurrentUser' => ($i === $currentUserTranche)
            ];
        }

        return $chartData;
    }

    const SLICES = 100;
    private function getVisitStats($season)
    {
        $memberVisits = $this->getMemberVisits($season);
        $visitCounts = array_values($memberVisits);
        if (empty($visitCounts)) {
            return [
                'tranches' => [],
                'distribution' => [],
                'currentUserTranche' => null
            ];
        }
        $minVisits = min($visitCounts);
        $maxVisits = max($visitCounts);
        $trancheSize = max(1, ceil(($maxVisits - $minVisits) / self::SLICES));
        $tranches = [];
        for ($i = 0; $i < self::SLICES; $i++) {
            $start = $minVisits + ($i * $trancheSize);
            $end = $start + $trancheSize - 1;
            if ($i == self::SLICES - 1) {
                $end = $maxVisits;
            }
            $tranches[] = [
                'start' => $start,
                'end' => $end,
                'label' => "$start-$end"
            ];
        }
        $distribution = array_fill(0, count($tranches), 0);
        foreach ($memberVisits as $visits) {
            $index = ($trancheSize > 0)
                ? floor(($visits - $minVisits) / $trancheSize)
                : 0;
            if ($index >= self::SLICES) $index = self::SLICES - 1;
            $distribution[$index]++;
        }
        $mergedTranches = [];
        $mergedDistribution = [];
        $currentTranche = null;
        $currentCount = 0;
        for ($i = 0; $i < count($tranches); $i++) {
            if ($distribution[$i] === 0) {
                if ($currentTranche === null) {
                    $currentTranche = $tranches[$i];
                    $currentCount = 0;
                } else {
                    $currentTranche['end'] = $tranches[$i]['end'];
                    $currentTranche['label'] = "{$currentTranche['start']}-{$currentTranche['end']}";
                }
            } else {
                if ($currentTranche !== null) {
                    $mergedTranches[] = $currentTranche;
                    $mergedDistribution[] = $currentCount;
                    $currentTranche = null;
                    $currentCount = 0;
                }
                $mergedTranches[] = $tranches[$i];
                $mergedDistribution[] = $distribution[$i];
            }
        }
        if ($currentTranche !== null) {
            $mergedTranches[] = $currentTranche;
            $mergedDistribution[] = $currentCount;
        }

        return [
            'tranches' => $mergedTranches,
            'distribution' => $mergedDistribution,
            'memberVisits' => $memberVisits
        ];
    }

    private function getMemberVisits($season)
    {
        $visits = (new LogDataHelper())->getVisits($season);
        $memberVisits = [];
        $members = $this->dataHelper->gets('Person', ['Inactivated' => 0], 'Email');
        foreach ($members as $member) {
            $email = $member->Email;
            $memberVisits[$email] = isset($visits[$email]) ? (int)$visits[$email] : 0;
        }
        return $memberVisits;
    }

    private function getCurrentUserTranche($stats, $person)
    {
        if (empty($person) || empty($stats['memberVisits'])) die('$person or $stats can\'t be nulli n file ' . __FILE__ . ' at line ' . __LINE__);
        $email = $person->Email;
        if (!array_key_exists($email, $stats['memberVisits'])) die('User $email not found in stats in file ' . __FILE__ . ' at line ' . __LINE__);
        $userVisits = $stats['memberVisits'][$email];
        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $tranche = $stats['tranches'][$i];
            if ($userVisits >= $tranche['start'] && $userVisits <= $tranche['end']) return $i;
        }
        die('$user slice not found in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
