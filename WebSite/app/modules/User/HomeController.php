<?php

namespace app\modules\User;

use app\helpers\Application;
use app\helpers\Params;
use app\modules\Common\AbstractController;

class HomeController extends AbstractController
{
    private \app\models\ArticleDataHelper $articleDataHelper;
    private \app\models\SurveyDataHelper $surveyDataHelper;
    private \app\helpers\News $news;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->articleDataHelper = new \app\models\ArticleDataHelper($application);
        $this->surveyDataHelper = new \app\models\SurveyDataHelper($application);
        $this->news = new \app\helpers\News([
            $this->articleDataHelper,
            $this->surveyDataHelper,
            new \app\models\EventDataHelper($application),
            new \app\models\MessageDataHelper($application),
            new \app\models\PersonDataHelper($application),
        ]);
    }

    public function home(): void
    {
        $connectedUser = $this->connectedUser->get();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        
        $_SESSION['navbar'] = '';
        $userPendingSurveys = $userPendingDesigns = [];
        $userEmail = $_SESSION['user'] ?? '';
        
        if ($userEmail) {
            if ($connectedUser->person === null) {
                unset($_SESSION['user']);
                $this->raiseBadRequest("Unknown user with this email address {$userEmail}", __FILE__, __LINE__);
            }
            
            $pendingSurveyResponses = $this->surveyDataHelper->getPendingSurveyResponses();
            $userPendingSurveys = array_filter($pendingSurveyResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            
            $pendingDesignResponses = (new \app\models\DesignDataHelper($this->application))->getPendingDesignResponses();
            $userPendingDesigns = array_filter($pendingDesignResponses, function ($item) use ($userEmail) {
                return strcasecmp($item->Email, $userEmail) === 0;
            });
            
            $news = $this->news->anyNews($connectedUser);
        } else {
            $lang = \app\helpers\TranslationManager::getCurrentLanguage();
            Params::setParams([
                'href' => '/user/sign/in',
                'userImg' => '🫥',
                'userEmail' => '',
                'isAdmin' => false,
                'currentVersion' => Application::VERSION,
                'currentLanguage' => $lang,
                'supportedLanguages' => \app\helpers\TranslationManager::getSupportedLanguages(),
                'flag' => \app\helpers\TranslationManager::getFlag($lang),
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
                if (strtotime($spotlightUntil) >= strtotime(date('Y-m-d'))) {
                    $latestArticle = $this->articleDataHelper->getWithAuthor($articleId);
                }
            }
        }

        $this->render('Common/views/home.latte', Params::getAll([
            'latestArticle' => $latestArticle,
            'latestArticles' => $articles['latestArticles'],
            'homeHeader' => $this->dataHelper->get('Settings', ['Name' => 'Home_header'], 'Value')->Value ?? '',
            'homeFooter' => $this->dataHelper->get('Settings', ['Name' => 'Home_footer'], 'Value')->Value ?? '',
            'navItems' => $this->getNavItems($connectedUser->person ?? false),
            'publishedBy' => $articles['latestArticle']
                && $articles['latestArticle']->PublishedBy != $articles['latestArticle']->CreatedBy 
                ? (new \app\models\PersonDataHelper($this->application))->getPublisher($articles['latestArticle']->PublishedBy) : '',
            'latestArticleHasSurvey' => $this->surveyDataHelper->articleHasSurveyNotClosed($articles['latestArticle']->Id ?? 0),
            'pendingSurveys' => $userPendingSurveys,
            'pendingDesigns' => $userPendingDesigns,
            'news' => $news ?? false,
        ]));
    }

    public function helpHome(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        
        $this->render('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'Help_home'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => 0,
            'previousPage' => true
        ]);
    }

    public function legalNotice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        
        $content = $this->application->getLatte()->renderToString('Common/views/info.latte', [
            'content' => $this->dataHelper->get('Settings', ['Name' => 'LegalNotices'], 'Value')->Value ?? '',
            'hasAuthorization' => $this->connectedUser->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION
        ]);
        echo $content;
    }
}
