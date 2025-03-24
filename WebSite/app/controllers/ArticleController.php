<?php

namespace app\controllers;

use PDO;
use app\helpers\Backup;

class ArticleController extends TableController
{
    public function home(): void
    {
        if ($this->getPerson(['Redactor'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $_SESSION['navbar'] = 'redactor';

                echo $this->latte->render('app/views/admin/redactor.latte', $this->params->getAll([]));
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
            'createdBy' => $_GET['createdBy'] ?? '',
            'title' => $_GET['title'] ?? '',
            'timestamp' => $_GET['timestamp'] ?? '',
            'lastUpdate' => $_GET['lastUpdate'] ?? '',
            'published' => $_GET['published'] ?? ''
        ];
        $filterConfig = [
            ['name' => 'createdBy', 'label' => 'Créé par'],
            ['name' => 'title', 'label' => 'Titre'],
            ['name' => 'timestamp', 'label' => 'Date de création'],
            ['name' => 'lastUpdate', 'label' => 'Dernière modification'],
            ['name' => 'published', 'label' => 'Publié'],
            ['name' => 'groupName', 'label' => 'N° du groupe']
        ];
        $columns = [
            ['field' => 'CreatedBy', 'label' => 'Créé par'],
            ['field' => 'LastName', 'label' => 'Nom'],
            ['field' => 'FirstName', 'label' => 'Prénom'],
            ['field' => 'Title', 'label' => 'Titre'],
            ['field' => 'Timestamp', 'label' => 'Date de création'],
            ['field' => 'LastUpdate', 'label' => 'Dernière modification'],
            ['field' => 'Published', 'label' => 'Publié'],
            ['field' => 'IdGroup', 'label' => 'Groupe(n°)'],
            ['field' => 'HasSurvey', 'label' => 'Sondage']
        ];
        $query = $this->fluent->from('Article')
            ->select('Article.Id, Article.CreatedBy, Article.Title, Article.Timestamp, CASE WHEN Article.PublishedBy IS NULL THEN "non" ELSE "oui" END AS Published, Person.FirstName, Person.LastName')
            ->select('CASE WHEN Survey.IdArticle IS NOT NULL THEN "oui" ELSE "non" END AS HasSurvey')
            ->innerJoin('Person ON Article.CreatedBy = Person.Id')
            ->leftJoin('Survey ON Article.Id = Survey.IdArticle')
            ->where('(Article.IdGroup IS NULL)')
            ->where('(Article.PublishedBy IS NOT NULL)');
        if ($person) {
            if ($this->authorizations->isEditor()) {
                $query = $query->whereOr('1=1');
            } else {
                $query = $query->whereOr('Article.CreatedBy = ' . $person['Id'])
                    ->whereOr('Article.IdGroup IN (SELECT IdGroup FROM PersonGroup WHERE IdPerson = ' . $person['Id'] . ')');
            }
        }
        $query = $query->orderBy('Article.Timestamp DESC');
        $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
        echo $this->latte->render('app/views/user/articles.latte', $this->params->getAll([
            'articles' => $data['items'],
            'currentPage' => $data['currentPage'],
            'totalPages' => $data['totalPages'],
            'filterValues' => $filterValues,
            'filters' => $filterConfig,
            'columns' => $columns,
            'resetUrl' => '/articles',
            'isRedactor' => $person ? $this->authorizations->isRedactor() : false,
            'userConnected' => $person,
            'layout' => $this->getLayout()
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
        $articleIds = $this->getArticleIdsBasedOnAccess($person['Email'] ?? null);
        $chosenArticle = $this->getLatestArticle([$id]);
        $canEdit = false;
        if ($person && $chosenArticle) {
            $canEdit = ($person && $person['Id'] == $chosenArticle->CreatedBy);
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

        $survey = $this->fluent->from('Survey')
            ->where('IdArticle', $id)
            ->fetch();

        echo $this->latte->render('app/views/user/article.latte', $this->params->getAll([
            'chosenArticle' => $chosenArticle,
            'latestArticleTitles' => $this->getLatestArticleTitles($articleIds),
            'canEdit' => $canEdit,
            'groups' => $this->getGroups(),
            'hasSurvey' => $survey ? true : false,
            'id' => $id,
            'userConnected' => $person,
            'navItems' => $this->getNavItems(),
            'publishedBy' => $chosenArticle->PublishedBy && $chosenArticle->PublishedBy != $chosenArticle->CreatedBy ? $this->getPublisher($chosenArticle->PublishedBy) : '',
        ]));
    }

    public function update($id): void
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $article = $this->getLatestArticle([$id]);
                if (!$article || $person['Id'] != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                $published = $_POST['published'] ?? 0;
                $idGroup = $_POST['idGroup'] === '' ? null : ($_POST['idGroup'] ?? null);
                if (empty($title) || empty($content)) {
                    $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                    $this->flight->redirect('/articles/' . $id);
                    return;
                }

                $query = $this->pdo->prepare("UPDATE Article SET Title = ?, Content = ?, PublishedBy = ?, IdGroup = ?, LastUpdate = ? WHERE Id = ?");
                $result = $query->execute([$title, $content, $published == 1 ? $person['Id'] : NULL, $idGroup, date('Y-m-d H:i:s'), $id]);
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
                if (!$article || ($person['Id'] != $article->CreatedBy && !$this->authorizations->isEditor())) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $published = $_POST['published'] ?? 0;

                $query = $this->pdo->prepare("UPDATE Article SET PublishedBy = ?, LastUpdate = ?  WHERE Id = ?");
                $result = $query->execute([$published == 1 ? $person['Id'] : NULL, date('Y-m-d H:i:s'), $id]);
                if ($result) {
                    $_SESSION['success'] = "L'article a été mis à jour avec succès";
                    (new Backup())->save();
                } else {
                    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
                }
                $this->flight->redirect('/articles/' . $id);
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/user/publish.latte', $this->params->getAll([
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
                $query = $this->pdo->prepare("INSERT INTO Article (Title, Content, CreatedBy) VALUES ('', '', ?)");
                $query->execute([$person['Id']]);
                $id = $this->pdo->lastInsertId();
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
                if (!$article || $person['Id'] != $article->CreatedBy) {
                    $this->application->error403(__FILE__, __LINE__);
                    return;
                }
                $query = $this->pdo->prepare('DELETE FROM Article WHERE Id = ?');
                $query->execute([$id]);

                $this->flight->redirect('/articles');
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
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

        $userGroups = $this->getUserGroups($userEmail);
        if (empty($userGroups)) {
            return $noGroupArticleIds;
        }
        $groupArticleIds = $this->getArticleIdsByGroups($userGroups);
        return array_unique(array_merge($noGroupArticleIds, $groupArticleIds));
    }

    private function getNoGroupArticleIds(): array
    {
        $query = $this->pdo->prepare("
            SELECT Article.Id FROM Article 
            WHERE Article.publishedBy IS NOT NULL AND Article.IdGroup IS NULL");
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
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $query = $this->pdo->prepare("
            SELECT Article.*, Person.FirstName, Person.LastName, 'Group'.Name || '(' || 'Group'.Id || ')' AS GroupName
            FROM Article 
            LEFT JOIN Person ON Person.Id = Article.CreatedBy 
            LEFT JOIN 'Group' ON Article.IdGroup = 'Group'.Id
            WHERE Article.Id IN ($placeholders)
            ORDER BY Article.LastUpdate DESC 
            LIMIT 1");
        $query->execute($articleIds);
        return $query->fetch(PDO::FETCH_OBJ) ?: null;
    }

    private function getLatestArticleTitles(array $articleIds): array
    {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $query = $this->pdo->prepare("
            SELECT Id, Title, Timestamp, LastUpdate 
            FROM Article 
            WHERE Article.Id IN ($placeholders)
            AND Article.publishedBy IS NOT NULL 
            ORDER BY Article.LastUpdate DESC 
            LIMIT 10");
        $query->execute($articleIds);
        return $query->fetchAll(PDO::FETCH_OBJ) ?: [];
    }

    private function getGroups()
    {
        $query = $this->pdo->query("SELECT Id, Name FROM 'Group' WHERE Inactivated=0 ORDER BY Name");
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getArticle($id)
    {
        $query = $this->pdo->prepare("
        SELECT a.*, p.FirstName, p.LastName, p.NickName
        FROM Article a
        LEFT JOIN Person p ON a.CreatedBy = p.Id
        WHERE a.Id = ?");
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}
