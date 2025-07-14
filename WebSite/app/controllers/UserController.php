<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Alert;
use app\helpers\Article;
use app\helpers\Email;
use app\helpers\News;
use app\helpers\Params;
use app\helpers\PasswordManager;
use app\helpers\PersonStatistics;
use app\helpers\TranslationManager;

class UserController extends BaseController
{
    private Article $article;
    private Email $email;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->article = new Article($pdo);
        $this->email = new Email($pdo);
    }

    #region Sign
    public function forgotPassword($encodedEmail)
    {
        $email = urldecode($encodedEmail);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $person = $this->getPersonByEmail($email);

            if ($person) {
                if ($person->TokenCreatedAt === null || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1) {
                    $token = bin2hex(random_bytes(32));
                    $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');
                    $query = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
                    $query->execute([$token, $tokenCreatedAt, $person->Id]);
                    $resetLink = 'https://' . $_SERVER['HTTP_HOST'] . '/user/setPassword/' . $token;

                    $to = $email;
                    $subject = "Initialisation du mot de passe";
                    $message = "Cliquez sur ce lien pour initialiser votre mot de passe : $resetLink";

                    if (mail($to, $subject, $message)) {
                        $this->application->message('Un courriel a été envoyé pour réinitialiser votre mot de passe');
                    } else {
                        $this->application->message("Une erreur est survenue lors de l'envoi de l'email", 3000, 500);
                    }
                } else {
                    $this->application->message("Un courriel de réinitialisation a déjà été envoyé à " . substr($person->TokenCreatedAt, 10) . ". Il est valide pendant 1 heure.");
                }
            } else {
                $this->application->error480($email, __FILE__, __LINE__);
            }
        } else {
            $this->application->error481($email, __FILE__, __LINE__);
        }
    }

    public function setPassword($token)
    {
        $query = $this->pdo->prepare('SELECT * FROM "Person" WHERE Token = ?');
        $query->execute([$token]);
        $person = $query->fetch();

        if (!$person) {
            $this->application->error498('Person', $token, __FILE__, __LINE__);
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if ($person->TokenCreatedAt === null || (new DateTime($person->TokenCreatedAt))->diff(new DateTime())->h >= 1) {
                    $this->application->error497($token, __FILE__, __LINE__);
                } else {
                    $stmt = $this->pdo->prepare('UPDATE Person SET Password = ?, Token = null, TokenCreatedAt = null WHERE Id = ?');
                    $stmt->execute([PasswordManager::signPassword($_POST['password']), $person->Id]);

                    $this->application->message('Votre mot de passe est réinitialisé');
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/setPassword.latte', [
                    'href' => '/user/sign/in',
                    'userImg' => '/app/images/anonymat.png',
                    'userEmail' => '',
                    'keys' => false,
                    'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                    'token' => $token,
                    'currentVersion' => self::VERSION
                ]);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }

    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
            if ($email === '') {
                $this->application->error481($_POST['email'], __FILE__, __LINE__);
            } else {
                $password = $_POST['password'] ?? '';
                if (strlen($password) < 6 || strlen($password) > 30) {
                    $this->application->error482('password rules are not respected [6..30] characters', __FILE__, __LINE__);
                } else {
                    if (!$person = $this->getPersonByEmail($email)) {
                        $this->application->error480($email, __FILE__, __LINE__);
                    } else {
                        if ($person->Inactivated == 1) {
                            $this->application->error479($email, __FILE__, __LINE__);
                        } else {
                            if (PasswordManager::verifyPassword($password, $person->Password ?? '')) {
                                $rememberMe = isset($_POST['rememberMe']) && $_POST['rememberMe'] === 'on';
                                if ($rememberMe) {
                                    $token = bin2hex(random_bytes(32));
                                    $tokenCreatedAt = (new DateTime())->format('Y-m-d H:i:s');
                                    $query = $this->pdo->prepare('UPDATE Person SET Token = ?, TokenCreatedAt = ? WHERE Id = ?');
                                    $query->execute([$token, $tokenCreatedAt, $person->Id]);
                                    setcookie('rememberMe', $token, [
                                        'expires' => time() + 30 * 24 * 60 * 60, // 30 days
                                        'path' => '/',
                                        'secure' => true,
                                        'httponly' => true,
                                        'samesite' => 'Strict'
                                    ]);
                                }
                                $lastActivity = $this->fluentForLog->from('Log')
                                    ->select(null)
                                    ->select('CreatedAt')
                                    ->where('Who COLLATE NOCASE', $email)
                                    ->orderBy('Id DESC')
                                    ->limit(1)
                                    ->fetch('CreatedAt');
                                if ($lastActivity) {
                                    $this->fluent->update('Person')->set('LastSignOut', $lastActivity)->where('Email COLLATE NOCASE', $email)->execute();
                                }
                                $this->fluent->update('Person')->set(['LastSignIn' => date('Y-m-d H:i:s')])->where('Email COLLATE NOCASE', $email)->execute();
                                $_SESSION['user'] = $email;
                                $_SESSION['navbar'] = '';
                                $this->application->message("Sign in succeeded with $email", 1);
                            } else {
                                $this->application->error482("sign in failed with $email address", __FILE__, __LINE__);
                            }
                        }
                    }
                }
            }
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_COOKIE['rememberMe'])) {
                $token = $_COOKIE['rememberMe'];
                $person = $this->fluent->from('Person')->where('Token', $token)->fetch();
                if ($person && $person->Inactivated == 0) {
                    $this->fluent->update('Person')->set(['LastSignIn' => date('Y-m-d H:i:s')])->where('Id', $person->Id)->execute();
                    $_SESSION['user'] = $person->Email;
                    $_SESSION['navbar'] = '';
                } else {
                    setcookie('rememberMe', '', time() - 3600, '/');
                }
                $this->flight->redirect('/');
            }

            $this->render('app/views/user/signIn.latte', [
                'href' => '/user/sign/in',
                'userImg' => '/app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'page' => basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)),
                'currentVersion' => self::VERSION
            ]);
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }

    public function signOut()
    {
        $this->log(200, 'Sign out succeeded with with ' . $_SESSION['user'] ?? '');
        $this->fluent->update('Person')->set(['LastSignOut' => date('Y-m-d H:i:s')])->where('Email COLLATE NOCASE', $_SESSION['user'])->execute();
        unset($_SESSION['user']);
        $_SESSION['navbar'] = '';
        $this->flight->redirect('/');
    }
    #endregion

    public function home()
    {
        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = $_SESSION['user'] ?? '';
        if ($userEmail) {
            $person = $this->getPerson();
            if (!$person) {
                unset($_SESSION['user']);
                $this->application->error480($userEmail, __FILE__, __LINE__);
            }
            $pendingSurveyResponses = (new Alert($this->pdo))->getPendingSurveyResponses();
            $userPendingSurveys = array_filter($pendingSurveyResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            $pendingDesignResponses = (new Alert($this->pdo))->getPendingDesignResponses();
            $userPendingDesigns = array_filter($pendingDesignResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });

            $news = (new News($this->pdo))->anyNews($person);
        } else {
            $translationManager = new TranslationManager($this->pdo);
            $this->params = new Params([
                'href' => '/user/sign/in',
                'userImg' => '/app/images/anonymat.png',
                'userEmail' => '',
                'keys' => false,
                'currentVersion' => self::VERSION,
                'currentLanguage' => $translationManager->getCurrentLanguage(),
                'supportedLanguages' => $translationManager->getSupportedLanguages(),
                'flag' => $translationManager->getFlag($translationManager->getCurrentLanguage()),
                'isRedactor' => false,
            ]);
        }
        $articles = $this->article->getLatestArticles($userEmail);
        $latestArticle = $articles['latestArticle'];
        $spotlight = $this->article->getSpotlightArticle();
        if ($spotlight !== null) {
            $articleId = $spotlight['articleId'];
            if ($this->article->isUserAllowedToReadArticle($userEmail, $articleId)) {
                $spotlightUntil = $spotlight['spotlightUntil'];
                if (strtotime($spotlightUntil) >= strtotime(date('Y-m-d'))) {
                    $latestArticle = $this->article->getArticle($articleId);
                }
            }
        }
        $this->render('app/views/home.latte', $this->params->getAll([
            'latestArticle' => $latestArticle,
            'latestArticles' => $articles['latestArticles'],
            'greatings' => $this->settings->get('Greatings'),
            'link' => $this->settings->get('Link'),
            'navItems' => $this->getNavItems(),
            'publishedBy' => $articles['latestArticle'] && $articles['latestArticle']->PublishedBy != $articles['latestArticle']->CreatedBy ? $this->getPublisher($articles['latestArticle']->PublishedBy) : '',
            'latestArticleHasSurvey' => (new Article($this->pdo))->hasSurvey($articles['latestArticle']->Id ?? 0),
            'pendingSurveys' => $userPendingSurveys,
            'pendingDesigns' => $userPendingDesigns,
            'news' => $news ?? false,
        ]));
    }

    #region Data user
    public function user()
    {
        if ($this->getPerson()) {
            $_SESSION['navbar'] = 'user';
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/user.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function account()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ?? '';
                $password = $_POST['password'];
                $firstName = $_POST['firstName'];
                $lastName = $_POST['lastName'];
                $nickName = $_POST['nickName'];
                $avatar = pathinfo($_POST['avatar'], PATHINFO_BASENAME) ?? '';
                $useGravatar = $_POST['useGravatar'] ?? 'no';
                $query = $this->pdo->prepare('UPDATE Person SET FirstName = ?, LastName = ?, NickName = ?, Avatar = ?, useGravatar = ? WHERE Id = ' . $person->Id);
                $query->execute([$firstName, $lastName, $nickName, $avatar, $useGravatar]);

                if (!empty($password)) {
                    $query = $this->pdo->prepare('UPDATE Person SET Password = ? WHERE Id = ' . $person->Id);
                    $query->execute([PasswordManager::signPassword($password)]);
                }

                if ($person->Imported == 0) {
                    $query = $this->pdo->prepare('UPDATE Person SET Email = ? WHERE Id = ' . $person->Id);
                    $query->execute([$email]);
                    $_SESSION['user'] = $email;
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $email = filter_var($person->Email, FILTER_VALIDATE_EMAIL) ?? '';
                $firstName = $this->sanitizeInput($person->FirstName);
                $lastName = $this->sanitizeInput($person->LastName);
                $nickName = $this->sanitizeInput($person->NickName);
                $avatar = $this->sanitizeInput($person->Avatar);
                $useGravatar = $this->sanitizeInput($person->UseGravatar) ?? 'no';

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
                    'layout' => $this->getLayout()
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function availabilities()
    {
        if ($person = $this->getPerson([], 1)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $availabilities = $_POST['availabilities'] ?? '';
                if ($availabilities != '') {
                    $query = $this->pdo->prepare('UPDATE Person SET availabilities = ? WHERE Id = ' . $person->Id);
                    $query->execute([json_encode($availabilities)]);
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $currentAvailabilities = json_decode($person->Availabilities ?? '', true);
                $this->render('app/views/user/availabilities.latte', $this->params->getAll([
                    'currentAvailabilities' => $currentAvailabilities
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function preferences()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $preferences = $_POST['preferences'];
                $query = $this->pdo->prepare('UPDATE Person SET preferences = ? WHERE Id = ' . $person->Id);
                $query->execute([json_encode($preferences)]);
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $preferences = json_decode($person->Preferences ?? '', true);
                $query = $this->pdo->prepare("
                    SELECT et.*
                    FROM EventType et
                    LEFT JOIN `Group` g ON et.IdGroup = g.Id
                    WHERE et.Inactivated = 0 
                    AND (
                        g.Id IN (
                            SELECT pg.IdGroup
                            FROM PersonGroup pg
                            WHERE pg.IdPerson = ? AND pg.IdGroup = g.Id
                        )
                        OR et.IdGroup is NULL)
                    ORDER BY et.Name
                ");
                $query->execute([$person->Id]);
                $eventTypes = $query->fetchAll();

                $eventTypesWithAttributes = [];
                foreach ($eventTypes as $eventType) {
                    $queryAttributes = $this->pdo->prepare("
                        SELECT a.*
                        FROM Attribute a
                        JOIN EventTypeAttribute eta ON a.Id = eta.IdAttribute
                        WHERE eta.IdEventType = ?
                        ORDER BY a.Name
                    ");
                    $queryAttributes->execute([$eventType->Id]);
                    $eventType->Attributes = $queryAttributes->fetchAll();
                    $eventTypesWithAttributes[] = $eventType;
                }

                $this->render('app/views/user/preferences.latte', $this->params->getAll([
                    'currentPreferences' => $preferences,
                    'eventTypes' => $eventTypesWithAttributes
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function groups()
    {
        if ($person = $this->getPerson([], 1)) {

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $groups = $_POST['groups'] ?? [];
                $idPerson = $person->Id;
                $query = $this->pdo->prepare("
                DELETE FROM PersonGroup 
                WHERE IdPerson = $idPerson 
                AND IdGroup IN (SELECT Id FROM `Group` WHERE SelfRegistration = 1)");
                $query->execute();

                $query = $this->pdo->prepare('INSERT INTO PersonGroup (IdPerson, IdGroup) VALUES (?, ?)');
                foreach ($groups as $groupId) {
                    $query->execute([$idPerson, $groupId]);
                }
                $this->flight->redirect('/user');
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare('
                SELECT g.*, 
                    CASE WHEN pg.Id IS NOT NULL THEN 1 ELSE 0 END as isMember,
                    g.SelfRegistration as canToggle
                FROM `Group` g 
                LEFT JOIN PersonGroup pg ON pg.IdGroup = g.Id AND pg.IdPerson = ?
                WHERE g.Inactivated = 0 AND (g.SelfRegistration = 1 OR pg.Id IS NOT NULL)
                ORDER BY g.Name');
                $query->execute([$person->Id]);
                $currentGroups = $query->fetchAll();
                $this->render('app/views/user/groups.latte', $this->params->getAll([
                    'groups' => $currentGroups,
                    'layout' => $this->getLayout()
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }
    #endregion 

    public function help()
    {
        if ($this->getPerson()) {
            $this->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settings->get('Help_user'),
                'hasAuthorization' => $this->authorizations->hasAutorization(),
                'currentVersion' => self::VERSION
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function contact($eventId = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->getPerson();
            $this->render('app/views/contact.latte', $this->params->getAll([
                'navItems' => $this->getNavItems(),
                'event' => $eventId != null ? $this->fluent->from('Event')->where('Id', $eventId)->fetch() : null,
            ]));
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $message = trim($_POST['message'] ?? '');
            $errors = [];
            if (empty($name)) {
                $errors[] = 'Le nom et prénom sont requis.';
            }
            if (empty($email)) {
                $errors[] = 'L\'email est requis.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'email n\'est pas valide.';
            }
            if (empty($message)) {
                $errors[] = 'Le message est requis.';
            }
            if (empty($errors)) {
                $adminEmail = $this->settings->get('contactEmail');
                if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $this->application->error500('Invalid contactEmmail', __FILE__, __LINE__);
                }
                $eventId = trim($_POST['eventId'] ?? '');
                $event = $this->fluent->from('Event')->where('Id', $eventId)->fetch();
                if (!$event) {
                    $this->application->error471($eventId, __FILE__, __LINE__);
                    return false;
                }
                if (!empty($eventId)) $emailSent = $this->email->sendRegistrationLink($adminEmail, $name, $email, $event);
                else $emailSent = $this->email->sendContactEmail($adminEmail, $name, $email, $message);
                if ($emailSent) {
                    $url = $this->buildUrl('/contact', [
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
        if ($person = $this->getPerson([], 1)) {
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
                'news' => (new News($this->pdo))->getNewsForPerson($person, $searchFrom),
                'searchFrom' => $searchFrom,
                'searchMode' => $searchMode,
                'navItems' => $this->getNavItems(),
                'person' => $person
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    #region Statistics
    public function showStatistics()
    {
        if ($person = $this->getPerson([], 1)) {
            $personalStatistics = new PersonStatistics($this->pdo);
            $season = $personalStatistics->getSeasonRange();
            $this->render('app/views/user/statistics.latte', $this->params->getAll([
                'stats' => $personalStatistics->getStats($person, $season['start'], $season['end'], $this->authorizations->isWebmaster()),
                'seasons' => $personalStatistics->getAvailableSeasons(),
                'currentSeason' => $season,
                'navItems' => $this->getNavItems(),
                'chartData' => $this->getVisitStatsForChart($season, $person),
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
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
        $query = $this->pdoForLog->prepare("
            SELECT Who, COUNT(Id) as VisitCount
            FROM Log 
            WHERE CreatedAt BETWEEN :start AND :end
            GROUP BY Who
        ");
        $query->execute([
            ':start' => $season['start'],
            ':end' => $season['end']
        ]);
        $visits = $query->fetchAll(PDO::FETCH_KEY_PAIR);
        $memberVisits = [];
        $members = $this->fluent->from('Person')->select('Email')->where('Inactivated', 0)->fetchAll();
        foreach ($members as $member) {
            $email = $member->Email;
            $memberVisits[$email] = isset($visits[$email]) ? (int)$visits[$email] : 0;
        }
        return $memberVisits;
    }

    private function getCurrentUserTranche($stats, $person)
    {
        if (empty($person) || empty($stats['memberVisits'])) {
            die('$person or $stats can\'t be null');
        }

        $email = $person->Email;

        if (!array_key_exists($email, $stats['memberVisits'])) {
            die("User $email not found in stats.");
        }

        $userVisits = $stats['memberVisits'][$email];

        for ($i = 0; $i < count($stats['tranches']); $i++) {
            $tranche = $stats['tranches'][$i];
            if ($userVisits >= $tranche['start'] && $userVisits <= $tranche['end']) {
                return $i;
            }
        }

        die('$user slice not found');
    }
}
