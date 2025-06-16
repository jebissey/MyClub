<?php

namespace app\controllers;

use PDO;
use app\helpers\Article;
use app\helpers\Backup;

class ArticleController extends TableController
{
    public function home(): void
    {
        if ($this->getPerson(['Redactor'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'redactor';

                $this->render('app/views/admin/redactor.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function index()
    {
        $person = $this->getPerson([]);
        $filterValues = [
            'PersonName' => $_GET['PersonName'] ?? '',
            'title' => $_GET['title'] ?? '',
            'timestamp' => $_GET['timestamp'] ?? '',
            'lastUpdate' => $_GET['lastUpdate'] ?? '',
            'published' => $_GET['published'] ?? '',
            'GroupName' => $_GET['GroupName'] ?? '',
            'Content' => $_GET['Content'] ?? '',
        ];
        $filterConfig = [
            ['name' => 'PersonName', 'label' => 'Créé par'],
            ['name' => 'title', 'label' => 'Titre'],
            ['name' => 'lastUpdate', 'label' => 'Dernière modification'],
            ['name' => 'published', 'label' => 'Publié'],
            ['name' => 'GroupName', 'label' => 'Groupe'],
            ['name' => 'Content', 'label' => 'Contenu'],
        ];
        $columns = [
            ['field' => 'PersonName', 'label' => 'Créé par'],
            ['field' => 'Title', 'label' => 'Titre'],
            ['field' => 'LastUpdate', 'label' => 'Dernière modification'],
            ['field' => 'GroupName', 'label' => 'Groupe'],
            ['field' => 'ForMembers', 'label' => 'Club'],
            ['field' => 'Pool', 'label' => 'Sondage (votes)'],
        ];
        if ($this->authorizations->isWebmaster()) {
            $columns[] = ['field' => 'Published', 'label' => 'Publié'];
        }
        $query = $this->fluent->from('Article')
            ->select('Article.Id, Article.CreatedBy, Article.Title, Article.LastUpdate')
            ->select('CASE WHEN Article.PublishedBy IS NULL THEN "non" ELSE "oui" END AS Published')
            ->select('CASE WHEN Article.OnlyForMembers = 1 THEN "oui" ELSE "non" END AS ForMembers')
            ->select('
                CASE 
                    WHEN Survey.IdArticle IS NULL THEN "non"
                    ELSE 
                        (
                            CASE 
                                WHEN Survey.ClosingDate < CURRENT_DATE THEN "clos"
                                ELSE strftime("%d/%m/%Y", Survey.ClosingDate)
                            END
                            || " (" || COUNT(Reply.Id) || ") "
                            || CASE Survey.Visibility
                                WHEN "all" THEN "👁️‍🗨️👥"
                                WHEN "allAfterClosing" THEN "👁️‍🗨️👥📅"
                                WHEN "voters" THEN "👁️‍🗨️🗳️"
                                WHEN "votersAfterClosing" THEN "👁️‍🗨️🗳️📅"
                                WHEN "redactor" THEN "👁️‍🗨️📝"
                                ELSE ""
                            END
                        )
                END AS Pool
            ')
            ->select('CASE WHEN Person.NickName != "" THEN Person.FirstName || " " || Person.LastName || " (" || Person.NickName || ")" ELSE Person.FirstName || " " || Person.LastName END AS PersonName')
            ->select("'Group'.Name AS GroupName")
            ->innerJoin('Person ON Article.CreatedBy = Person.Id')
            ->leftJoin('Survey ON Article.Id = Survey.IdArticle')
            ->leftJoin('Reply ON Survey.Id = Reply.IdSurvey')
            ->leftJoin("'Group' ON 'Group'.Id = Article.IdGroup")
            ->groupBy('Article.Id');

        if ($person) {
            if (!$this->authorizations->isEditor()) {
                $query = $query->where('(Article.CreatedBy = ' . $person->Id . '
                    OR (Article.PublishedBy IS NOT NULL 
                        AND (Article.IdGroup IS NULL OR Article.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $person->Id . '))
                       ))');
            }
        } else {
            $query = $query->where('(Article.IdGroup IS NULL AND Article.OnlyForMembers = 0 AND Article.PublishedBy IS NOT NULL)');
        }
        $query = $query->orderBy('Article.LastUpdate DESC');
        $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
        $this->render('app/views/user/articles.latte', $this->params->getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $person ? $this->authorizations->isRedactor() : false,
            'userConnected' => $person,
            'layout' => $this->getLayout(),
            'navItems' => $this->getNavItems(),
        ]));
    }

    public function getLatestArticles(?string $userEmail = null): array
    {
        $articleIds = $this->getArticleIdsBasedOnAccess($userEmail);
        if (empty($articleIds)) {
            return [
                'latestArticle' => null,
                'latestArticleTitles' => []
            ];
        }
        return [
            'latestArticle' => $this->getLatestArticle($articleIds),
            'latestArticleTitles' => $this->getLatestArticleTitles($articleIds)
        ];
    }

    public function show($id): void
    {
        $person = $this->getPerson();
        $article = $this->authorizations->getArticle($id, $person);
        if ($article) {
            $articleIds = $this->getArticleIdsBasedOnAccess($person->Email ?? null);
            $chosenArticle = $this->getLatestArticle([$id]);
            $canEdit = false;
            if ($person && $chosenArticle) {
                $canEdit = ($person && $person->Id == $chosenArticle->CreatedBy);
            }
            $messages = [];
            if (isset($_SESSION['error'])) {
                $messages['error'] = $_SESSION['error'];
                $_SESSION['error'] = null;
            }
            if (isset($_SESSION['success'])) {
                $messages['success'] = $_SESSION['success'];
                $_SESSION['success'] = null;
            }

            $this->render('app/views/user/article.latte', $this->params->getAll([
                'chosenArticle' => $chosenArticle,
                'latestArticleTitles' => $this->getLatestArticleTitles($articleIds),
                'canEdit' => $canEdit,
                'groups' => $this->getGroups(),
                'hasSurvey' =>  $this->fluent->from('Survey')->where('IdArticle', $id)->fetch(),
                'id' => $id,
                'userConnected' => $person,
                'navItems' => $this->getNavItems(),
                'publishedBy' => $chosenArticle->PublishedBy && $chosenArticle->PublishedBy != $chosenArticle->CreatedBy ? $this->getPublisher($chosenArticle->PublishedBy) : '',
                'latestArticleHasSurvey' => (new Article($this->pdo))->hasSurvey($id),
                'canReadPool' => $this->authorizations->canPersonReadSurveyResults($chosenArticle, $person),
                'carouselItems' => $this->fluent->from('Carousel')->where('IdArticle', $id)->fetchAll(),
            ]));
        } else if ($person == '') {
            $this->application->message('Il faut être connecté pour pouvoir consuler cet article', 5000, 403);
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function update($id): void
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->getLatestArticle([$id]);
                if (!$article || $person->Id != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                $published = $_POST['published'] ?? 0;
                $idGroup = $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null);
                $membersOnly = $_POST['membersOnly'] ?? 0;
                if (empty($title) || empty($content)) {
                    $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                    $this->flight->redirect('/articles/' . $id);
                    return;
                }
                $result = $this->fluent->update('Article')
                    ->set([
                        'Title'          => $title,
                        'Content'        => $content,
                        'PublishedBy'    => ($published == 1 ? $person->Id : null),
                        'IdGroup'        => $idGroup,
                        'OnlyForMembers' => $membersOnly,
                        'LastUpdate'     => date('Y-m-d H:i:s')
                    ])
                    ->where('Id', $id)
                    ->execute();

                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                }
                $this->flight->redirect('/articles/' . $id);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function publish($id): void
    {
        if ($person = $this->getPerson(['Editor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->getLatestArticle([$id]);
                if (!$article || ($person->Id != $article->CreatedBy && !$this->authorizations->isEditor())) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $published = $_POST['published'] ?? 0;
                $result = $this->fluent->update('Article')
                    ->set([
                        'PublishedBy' => ($published == 1 ? $person->Id : null),
                        'LastUpdate'  => date('Y-m-d H:i:s')
                    ])
                    ->where('Id', $id)
                    ->execute();
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                }
                $this->flight->redirect('/articles/' . $id);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->render('app/views/user/publish.latte', $this->params->getAll([
                    'article' => $this->getArticle($id),
                ]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function create()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $id = $this->fluent->insertInto('Article', [
                    'Title'     => '',
                    'Content'   => '',
                    'CreatedBy' => $person->Id
                ])
                    ->execute();
                $this->flight->redirect('/articles/' . $id);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function delete($id)
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if (($_SERVER['REQUEST_METHOD'] === 'GET')) {
                $article = $this->getLatestArticle([$id]);
                if (!$article || $person->Id != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $this->fluent->deleteFrom('Article')
                    ->where('Id', $id)
                    ->execute();

                $this->flight->redirect('/articles');
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function showArticleCrosstab()
    {
        if ($this->getPerson(['Redactor'], 1)) {
            $period = $this->flight->request()->query->period ?? 'month';

            $articleStatistics = new Article($this->pdo);
            $dateRange = $articleStatistics->getDateRangeForPeriod($period);
            $crosstabData = $articleStatistics->getAuthorAudienceCrosstab(
                $dateRange['start'],
                $dateRange['end']
            );
            $this->render('app/views/articles/crosstab.latte', $this->params->getAll([
                'crosstabData' => $crosstabData,
                'period' => $period,
                'dateRange' => $dateRange,
                'availablePeriods' => $articleStatistics->getAvailablePeriods(),
                'pdo' => $this->pdo,
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    private function getArticleIdsBasedOnAccess(?string $userEmail): array
    {
        $noGroupArticleIds = $this->getNoGroupArticleIds();
        if (empty($userEmail)) {
            return $noGroupArticleIds;
        }
        $forMembersOnlyArticleIds = $this->getArticleIdsForMembers([$userEmail]);
        if (empty($forMembersOnlyArticleIds)) {
            $articleIds = $noGroupArticleIds;
        } else {
            $articleIds = array_merge($noGroupArticleIds, $forMembersOnlyArticleIds);
        }
        $userGroups = $this->authorizations->getUserGroups($userEmail);
        if (empty($userGroups)) {
            return $articleIds;
        }
        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);
        return array_unique(array_merge($articleIds, $groupArticleIds));
    }

    private function getNoGroupArticleIds(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 0");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getArticleIdsForMembers(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL AND Article.OnlyForMembers = 1");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getArticleIdsByGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }
        $groups = implode(',', array_fill(0, count($groupIds), '?'));
        $query = $this->pdo->prepare("
            SELECT DISTINCT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL
            AND Article.IdGroup IN ($groups)");
        $query->execute($groupIds);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getLatestArticle(array $articleIds): ?object
    {
        if (empty($articleIds)) {
            return null;
        }

        $article = $this->fluent->from('Article')
            ->select('Article.*, Person.FirstName, Person.LastName, "Group".Name || \'(\' || "Group".Id || \')\' AS GroupName')
            ->leftJoin('Person ON Person.Id = Article.CreatedBy')
            ->leftJoin('"Group" ON Article.IdGroup = "Group".Id')
            ->where('Article.Id', $articleIds)
            ->orderBy('Article.LastUpdate DESC')
            ->limit(1)
            ->fetch();

        return $article ?: null;
    }

    private function getLatestArticleTitles(array $articleIds): array
    {
        if (empty($articleIds)) {
            return [];
        }

        return $this->fluent->from('Article')
            ->select('Id, Title, Timestamp, LastUpdate')
            ->where('Id', $articleIds)
            ->where('publishedBy IS NOT NULL')
            ->orderBy('LastUpdate DESC')
            ->limit(10)
            ->fetchAll() ?: [];
    }


    private function getArticle($id)
    {
        return $this->fluent
            ->from('Article a')
            ->leftJoin('Person p ON a.CreatedBy = p.Id')
            ->select('a.*, p.FirstName, p.LastName, p.NickName')
            ->where('a.Id', $id)
            ->fetch();
    }
}
