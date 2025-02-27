<?php

namespace app\controllers;

use PDO;


class ArticleController extends TableController
{
    public function index()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            $filterValues = [
                'createdBy' => $_GET['createdBy'] ?? '',
                'title' => $_GET['title'] ?? '',
                'timestamp' => $_GET['timestamp'] ?? '',
                'published' => $_GET['published'] ?? ''
            ];
            $filterConfig = [
                ['name' => 'createdBy', 'label' => 'Créé par'],
                ['name' => 'title', 'label' => 'Titre'],
                ['name' => 'timestamp', 'label' => 'Date de création'],
                ['name' => 'published', 'label' => 'Publié']
            ];
            $columns = [
                ['field' => 'CreatedBy', 'label' => 'Créé par'],
                ['field' => 'LastName', 'label' => 'Nom'],
                ['field' => 'FirstName', 'label' => 'Prénom'],
                ['field' => 'Title', 'label' => 'Titre'],
                ['field' => 'Timestamp', 'label' => 'Date de création'],
                ['field' => 'Published', 'label' => 'Publié']
            ];
            $query = $this->fluent->from('Article')
                ->select('Article.Id, Article.CreatedBy, Article.Title, Article.Timestamp, Article.Published, Person.FirstName, Person.LastName')
                ->innerJoin('Person ON Article.CreatedBy = Person.Id')
                ->orderBy('Article.Timestamp DESC');
            $data = $this->prepareTableData($query, $filterValues, $_GET['tablePage'] ?? null);
            echo $this->latte->render('app/views/user/articles.latte', $this->params->getAll([
                'articles' => $data['items'],
                'currentPage' => $data['currentPage'],
                'totalPages' => $data['totalPages'],
                'filterValues' => $filterValues,
                'filters' => $filterConfig,
                'columns' => $columns,
                'resetUrl' => '/articles',
                'conditionValue' => $person['Id'],
                'conditionColumn' => 'CreatedBy'
            ]));
        }
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

        echo $this->latte->render('app/views/user/article.latte', $this->params->getAll([
            'chosenArticle' => $chosenArticle,
            'latestArticleTitles' => $this->getLatestArticleTitles($articleIds),
            'canEdit' => $canEdit
        ]));
    }

    public function update($id): void
    {
        if ($person = $this->getPerson(['Redactor'])) {
            $article = $this->getLatestArticle([$id]);
            if (!$article || $person['Id'] != $article->CreatedBy) {
                $this->application->error403(__FILE__, __LINE__);
                return;
            }
            $title = $this->flight->request()->data['title'] ?? '';
            $content = $this->flight->request()->data['content'] ?? '';
            if (empty($title) || empty($content)) {
                $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                $this->flight->redirect('/articles/' . $id);
                return;
            }

            $query = $this->pdo->prepare("UPDATE Article SET Title = ?, Content = ? WHERE Id = ?");
            $result = $query->execute([$title, $content, $id]);
            if ($result) {
                $_SESSION['success'] = "L'article a été mis à jour avec succès";
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
            }
            $this->flight->redirect('/articles/' . $id);
        }
    }

    public function create()
    {
        if ($person = $this->getPerson(['Redactor'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $query = $this->pdo->prepare("INSERT INTO Article (Title, Content, CreatedBy) VALUES ('', '', ?)");
                $query->execute([$person['Id']]);
                $id = $this->pdo->lastInsertId();
                $this->flight->redirect('/articles/edit/' . $id);
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
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
            WHERE Article.published = 1 
            AND NOT EXISTS (
                SELECT 1 FROM ArticleGroup 
                WHERE ArticleGroup.IdArticle = Article.Id
            )");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getUserGroups(string $userEmail): array
    {
        $query = $this->pdo->prepare("
            SELECT PersonGroup.IdGroup 
            FROM PersonGroup 
            LEFT JOIN Person ON Person.Id = PersonGroup.IdPerson 
            WHERE Person.Email = ?");
        $query->execute([$userEmail]);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getArticleIdsByGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $query = $this->pdo->prepare("
            SELECT DISTINCT Article.Id FROM Article 
            JOIN ArticleGroup ON ArticleGroup.IdArticle = Article.Id 
            WHERE Article.published = 1 
            AND ArticleGroup.IdGroup IN ($placeholders)");
        $query->execute($groupIds);
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getLatestArticle(array $articleIds): ?object
    {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $query = $this->pdo->prepare("
            SELECT Article.*, Person.FirstName, Person.LastName 
            FROM Article 
            LEFT JOIN Person ON Person.Id = Article.CreatedBy 
            WHERE Article.Id IN ($placeholders)
            ORDER BY Article.Timestamp DESC 
            LIMIT 1");
        $query->execute($articleIds);
        return $query->fetch(PDO::FETCH_OBJ) ?: null;
    }

    private function getLatestArticleTitles(array $articleIds): array
    {
        $placeholders = implode(',', array_fill(0, count($articleIds), '?'));
        $query = $this->pdo->prepare("
            SELECT Article.Id, Article.Title, Article.Timestamp 
            FROM Article 
            WHERE Article.Id IN ($placeholders)
            AND Article.published = 1 
            ORDER BY Article.Timestamp DESC 
            LIMIT 10");
        $query->execute($articleIds);
        return $query->fetchAll(PDO::FETCH_OBJ) ?: [];
    }
}
