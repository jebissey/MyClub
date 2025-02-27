<?php

namespace app\controllers;

use PDO;


class ArticleController extends BaseController
{
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
                $this->flight->redirect('/article/' . $id);
                return;
            }
            $title = $this->flight->request()->data['title'] ?? '';
            $content = $this->flight->request()->data['content'] ?? '';
            if (empty($title) || empty($content)) {
                $_SESSION['error'] = "Le titre et le contenu sont obligatoires";
                $this->flight->redirect('/article/' . $id);
                return;
            }

            $query = $this->pdo->prepare("
                UPDATE Article 
                SET Title = ?, Content = ? 
                WHERE Id = ?
            ");
            $result = $query->execute([$title, $content, $id]);
            if ($result) {
                $_SESSION['success'] = "L'article a été mis à jour avec succès";
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de l'article";
            }
            $this->flight->redirect('/article/' . $id);
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
            AND Article.published = 1 
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
