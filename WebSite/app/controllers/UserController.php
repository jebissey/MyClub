<?php

namespace app\controllers;

use RuntimeException;

use app\enums\ApplicationError;
use app\enums\FilterInputRule;
use app\enums\Period;
use app\enums\YesNo;
use app\helpers\Application;
use app\helpers\ArticleDataHelper;
use app\helpers\AttributeDataHelper;
use app\helpers\DesignDataHelper;
use app\helpers\Email;
use app\helpers\EventDataHelper;
use app\helpers\EventTypeDataHelper;
use app\helpers\GroupDataHelper;
use app\helpers\LogDataHelper;
use app\helpers\MessageDataHelper;
use app\helpers\News;
use app\helpers\Params;
use app\helpers\Password;
use app\helpers\PersonDataHelper;
use app\helpers\PersonGroupDataHelper;
use app\helpers\PersonStatistics;
use app\helpers\Sign;
use app\helpers\SurveyDataHelper;
use app\helpers\TranslationManager;
use app\helpers\WebApp;

class UserController extends AbstractController
{
    private ArticleDataHelper $articleDataHelper;
    private Email $email;
    private News $news;
    private Sign $sign;
    private SurveyDataHelper $surveyDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->articleDataHelper = new ArticleDataHelper($application);
        $this->email = new Email();
        $this->sign = new Sign($this->application);
        $this->surveyDataHelper = new SurveyDataHelper($application);
        $this->news =  new News([
            new ArticleDataHelper($application),
            new SurveyDataHelper($application),
            new EventDataHelper($application),
            new MessageDataHelper($application),
            new PersonDataHelper($application),
        ]);
    }

    #region Sign
    public function forgotPassword($encodedEmail)
    {
        $this->sign->forgotPassword(urldecode($encodedEmail));
    }

    public function setPassword($token)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = WebApp::getFiltered('password', FilterInputRule::Password->value, $this->flight->request()->data->getData());
            if ($newPassword != null) $this->sign->checkAndSetPassword($token, $newPassword);
            else $this->application->getErrorManager()->raise(ApplicationError::BadRequest, 'Le nouveau mot de passe n\'est pas correct. Il doit faire entre 6 et 30 caractères.', 5000, false);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->render('app/views/user/setPassword.latte', [
                'href' => '/user/sign/in',
                'userImg' => '🫥',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'token' => $token,
                'currentVersion' => Application::VERSION
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $schema = [
                'email' => FilterInputRule::Email->value,
                'password' => FilterInputRule::Password->value,
                'rememberMe' => ['on'],
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            if ($input['email'] == null)
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, 'Invalid email address: ' . ($input['email'] ?? '') . ' in file ' . __FILE__ . ' at line ' . __LINE__);
            else {
                if ($input['password'] == null)
                    $this->application->getErrorManager()->raise(ApplicationError::BadRequest, 'password rules are not respected [6..30] characters in file ' . __FILE__ . ' at line ' . __LINE__);
                else $this->sign->authenticate($input['email'], $input['password'], ($input['rememberMe'] ?? '') === 'on');
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_COOKIE['rememberMe'])) {
                $token = $_COOKIE['rememberMe'];
                $person = $this->dataHelper->get('Person', ['Token' => $token], 'Id, Inactivated, Email');
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
                'userImg' => '🫥',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'currentVersion' => Application::VERSION
            ]);
        } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function signOut(): void
    {
        $user = $_SESSION['user'] ?? '';
        (new LogDataHelper($this->application))->add(200, 'Sign out succeeded with with ' . $user);
        $this->dataHelper->set('Person',  ['LastSignOut' => date('Y-m-d H:i:s')], ['Email' => $user]);
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
        $this->flight->redirect('/');
    }
    #endregion

    public function helpHome(): void
    {
        $content = $this->application->getLatte()->renderToString('app/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_home'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION
        ]);
        echo $content;
    }

    public function legalNotice(): void
    {
        $content = $this->application->getLatte()->renderToString('app/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'LegalNotices'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION
        ]);
        echo $content;
    }

    public function home(): void
    {
        $connectedUser = $this->connectedUser->get();
        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            if (!($connectedUser->person ?? false)) {
                unset($_SESSION['user']);
                $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown user with this email address: $userEmail in file " . __FILE__ . ' at line ' . __LINE__);
            }
            $pendingSurveyResponses = $this->surveyDataHelper->getPendingSurveyResponses();
            $userPendingSurveys = array_filter($pendingSurveyResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            $pendingDesignResponses = (new DesignDataHelper($this->application))->getPendingDesignResponses();
            $userPendingDesigns = array_filter($pendingDesignResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            $news = $this->news->anyNews($connectedUser);
        } else {
            $lang = TranslationManager::getCurrentLanguage();
            Params::setParams([
                'href' => '/user/sign/in',
                'userImg' => '🫥',
                'userEmail' => '',
                'keys' => false,
                'currentVersion' => Application::VERSION,
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
                if (strtotime($spotlightUntil) >= strtotime(date('Y-m-d'))) $latestArticle = $this->articleDataHelper->getWithAuthor($articleId);
            }
        }

        $this->render('app/views/home.latte', Params::getAll([
            'latestArticle' => $latestArticle,
            'latestArticles' => $articles['latestArticles'],
            'greatings' => $this->dataHelper->get('Settings', ['Name' => 'Greatings'], 'Value')->Value ?? '',
            'link' => $this->dataHelper->get('Settings', ['Name' => 'Link'], 'Value')->Value ?? '',
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'publishedBy' => $articles['latestArticle']
                && $articles['latestArticle']->PublishedBy != $articles['latestArticle']->CreatedBy ? (new PersonDataHelper($this->application))->getPublisher($articles['latestArticle']->PublishedBy) : '',
            'latestArticleHasSurvey' => $this->surveyDataHelper->articleHasSurveyNotClosed($articles['latestArticle']->Id ?? 0),
            'pendingSurveys' => $userPendingSurveys,
            'pendingDesigns' => $userPendingDesigns,
            'news' => $news ?? false,
        ]));
    }

    #region Data user
    public function user(): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            $_SESSION['navbar'] = 'user';
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/user.latte', Params::getAll([]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function account(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $schema = [
                    'email' => FilterInputRule::Email->value,
                    'password' => FilterInputRule::Password->value,
                    'firstName' => FilterInputRule::PersonName->value,
                    'lastName' => FilterInputRule::PersonName->value,
                    'nickName' => FilterInputRule::PersonName->value,
                    'useGravatar' => $this->application->enumToValues(YesNo::class),
                    'avatar' => FilterInputRule::Avatar->value,
                ];
                $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
                $this->dataHelper->set('Person', [
                    'FirstName' => $input['firstName'] ?? '',
                    'LastName' => $input['lastName'] ?? '',
                    'NickName' => $input['nickName'] ?? '',
                    'Avatar' => ($input['useGravatar'] ?? '') == YesNo::Yes->value ? '' : $input['avatar'],
                    'useGravatar' => $input['useGravatar'] ?? YesNo::No->value,
                ], ['Id' => $person->Id]);
                if (!empty($password))
                    $this->dataHelper->set('Person', ['Password' => Password::signPassword($input['password'])], ['Id' => $person->Id]);
                if ($person->Imported == 0) {
                    $this->dataHelper->set('Person', ['Email' => $input['email']], ['Id' => $person->Id]);
                    $_SESSION['user'] = $input['email'];
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/account.latte', Params::getAll([
                    'readOnly' => $person->Imported == 1 ? true : false,
                    'email' => filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?: '',
                    'firstName' => WebApp::sanitizeInput($person->FirstName),
                    'lastName' => WebApp::sanitizeInput($person->LastName),
                    'nickName' => WebApp::sanitizeInput($person->NickName),
                    'avatar' => WebApp::sanitizeInput($person->Avatar),
                    'useGravatar' => WebApp::sanitizeInput($person->UseGravatar, $this->application->enumToValues(YesNo::class), YesNo::No->value),
                    'emojis' => Application::EMOJI_LIST,
                    'isSelfEdit' => true,
                    'layout' => WebApp::getLayout()
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function availabilities(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $availabilities = WebApp::getFiltered('availabilities', FilterInputRule::Json->value, $this->flight->request()->data->getData()) ?? '';
                if ($availabilities != '') $this->dataHelper->set('Person', ['Availabilities' => json_encode($availabilities)], ['Id' => $person->Id]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentAvailabilities = json_decode($person->Availabilities ?? '', true);
                $this->render('app/views/user/availabilities.latte', Params::getAll(['currentAvailabilities' => $currentAvailabilities]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function preferences(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = WebApp::getFiltered('preferences', FilterInputRule::Json->value, $this->flight->request()->data->getData()) ?? '';
                $this->dataHelper->set('Person', ['preferences' =>  json_encode($preferences)], ['Id' => $person->Id]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $eventTypes = (new EventTypeDataHelper($this->application))->getsFor($person->Id);
                $eventTypesWithAttributes = [];
                $attributeDataHelper = new AttributeDataHelper($this->application);
                foreach ($eventTypes as $eventType) {
                    $eventType->Attributes = $attributeDataHelper->getAttributesOf($eventType->Id);
                    $eventTypesWithAttributes[] = $eventType;
                }

                $this->render('app/views/user/preferences.latte', Params::getAll([
                    'currentPreferences' => json_decode($person->Preferences ?? '', true),
                    'eventTypes' => $eventTypesWithAttributes
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function groups(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $groups = WebApp::getFiltered('groups', FilterInputRule::ArrayInt->value, $this->flight->request()->data->getData());
                (new PersonGroupDataHelper($this->application))->update($person->Id, $groups ?? []);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentGroups = (new GroupDataHelper($this->application))->getCurrentGroups($person->Id);

                $this->render('app/views/user/groups.latte', Params::getAll([
                    'groups' => $currentGroups,
                    'layout' => WebApp::getLayout()
                ]));
            } else $this->application->getErrorManager()->raise(ApplicationError::MethodNotAllowed, 'Method ' . $_SERVER['REQUEST_METHOD'] . ' is invalid in file ' . __FILE__ . ' at line ' . __LINE__);
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
    #endregion 

    public function help(): void
    {
        if ($this->connectedUser->get()->person ?? false) {
            $this->render('app/views/info.latte', Params::getAll([
                'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_user'], 'Value')->Value ?? '',
                'hasAuthorization' => $this->connectedUser->hasAutorization(),
                'currentVersion' => Application::VERSION
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    public function contact($eventId = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            $this->render('app/views/contact.latte', Params::getAll([
                'navItems' => $this->getNavItems($this->connectedUser->get()->person ?? false),
                'event' => $eventId != null ? $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary') : null,
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $schema = [
                'name' => FilterInputRule::PersonName->value,
                'email' => FilterInputRule::Email->value,
                'message' => FilterInputRule::HtmlSafeText->value,
                'eventId' => FilterInputRule::Int->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->data->getData());
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $message = $input['message'] ?? '';
            $errors = [];
            if (empty($name)) $errors[] = 'Nom et prénom sont requis.';
            if (empty($email)) $errors[] = 'Un email valide est requis.';
            if (empty($message)) $errors[] = 'Le message est requis.';
            if (empty($errors)) {
                $adminEmail = $this->dataHelper->get('Settings', ['Name' => 'contactEmail'], 'Value')->Value ?? '';
                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->application->getErrorManager()->raise(ApplicationError::InvalidSetting, 'Invalid contactEmmail', __FILE__, __LINE__);
                    return;
                }
                $eventId = $input['eventId'];
                if ($eventId != null) {
                    $event = $this->dataHelper->get('Event', ['Id' => $eventId], 'Id, Summary');
                    if (!$event) $this->application->getErrorManager()->raise(ApplicationError::BadRequest, "Unknown event '$eventId' in file " . __FILE__ . ' at line ' . __LINE__);
                }
                if ($eventId != null) $emailSent = (new PersonDataHelper($this->application))->sendRegistrationLink($adminEmail, $name, $email, $event);
                else $emailSent = $this->email->sendContactEmail($adminEmail, $name, $email, $message);
                if ($emailSent) {
                    $url = (new WebApp($this->application))->buildUrl('/contact', [
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
    public function showNews(): void
    {
        $connectedUser = $this->connectedUser->get(1);
        if ($connectedUser->person ?? false) {
            $searchMode = WebApp::getFiltered('from', $this->application->enumToValues(Period::class), $this->flight->request()->query->getData()) ?: Period::Signout->value;
            if ($searchMode === Period::Signin->value)      $searchFrom = $connectedUser->person->LastSignIn ?? '';
            elseif ($searchMode === Period::Signout->value) $searchFrom = $connectedUser->person->LastSignOut ?? '';
            elseif ($searchMode === Period::Week->value)    $searchFrom = date('Y-m-d H:i:s', strtotime('-1 week'));
            elseif ($searchMode === Period::Month->value)   $searchFrom = date('Y-m-d H:i:s', strtotime('-1 month'));

            $this->render('app/views/user/news.latte', Params::getAll([
                'news' => $this->news->getNewsForPerson($connectedUser, $searchFrom),
                'searchFrom' => $searchFrom,
                'searchMode' => $searchMode,
                'navItems' => $this->getNavItems($connectedUser->person ?? false),
                'person' => $connectedUser->person
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }

    #region Statistics
    public function showStatistics(): void
    {
        if ($person = $this->connectedUser->get(1)->person ?? false) {
            $personalStatistics = new PersonStatistics($this->application);
            $schema = [
                'seasonStart' => FilterInputRule::DateTime->value,
                'seasonEnd' => FilterInputRule::DateTime->value,
            ];
            $input = WebApp::filterInput($schema, $this->flight->request()->query->getData());
            $season = $personalStatistics->getSeasonRange($input['seasonStart'] ?: null, $input['seasonEnd'] ?: null);
            $this->render('app/views/user/statistics.latte', Params::getAll([
                'stats' => $personalStatistics->getStats($person, $season['start'], $season['end'], $this->connectedUser->isWebmaster()),
                'seasons' => $personalStatistics->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems($person),
                'chartData' => $this->getVisitStatsForChart($season, $person),
            ]));
        } else $this->application->getErrorManager()->raise(ApplicationError::Forbidden, 'Page not allowed in file ' . __FILE__ . ' at line ' . __LINE__);
    }
    private function getVisitStatsForChart(array $season, object $person): array
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
        $visits = (new LogDataHelper($this->application))->getVisits($season);
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
        if (empty($person) || empty($stats['memberVisits'])) throw new RuntimeException('$person or $stats can\'t be nulli n file ' . __FILE__ . ' at line ' . __LINE__);
        $email = $person->Email;
        if (!array_key_exists($email, $stats['memberVisits'])) throw new RuntimeException('User $email not found in stats in file ' . __FILE__ . ' at line ' . __LINE__);
        $userVisits = $stats['memberVisits'][$email];
        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $tranche = $stats['tranches'][$i];
            if ($userVisits >= $tranche['start'] && $userVisits <= $tranche['end']) return $i;
        }
        throw new RuntimeException('$user slice not found in file ' . __FILE__ . ' at line ' . __LINE__);
    }
}
