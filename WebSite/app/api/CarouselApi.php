<?php

namespace app\api;

use Exception;
use app\controllers\BaseController;

class CarouselApi extends BaseController
{
    public function getItems($idArticle)
    {
        $person = $this->getPerson();
        $article = $this->getArticle($idArticle);

        if (!$article || !$this->canAccessArticle($article, $person)) {
            $this->jsonResponse(['error' => 'Accès non autorisé'], 403);
            return;
        }
        $items = $this->fluent->from('Carousel')->where('IdArticle', $idArticle)->fetchAll();
        $this->jsonResponse(['success' => true, 'items' => $items]);
    }

    public function saveItem()
    {
        $person = $this->getPerson();
        if (!$person) {
            $this->jsonResponse(['error' => 'Utilisateur non connecté'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->jsonResponse(['error' => 'Données invalides'], 400);
            return;
        }

        $article = $this->getArticle($data['idArticle']);
        if (!$article || $article->CreatedBy != $person->Id) {
            $this->jsonResponse(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }

        $item = $this->sanitizeHtml($data['item']);
        try {
            if (!empty($data['id'])) {
                $result = $this->fluent->update('Carousel')
                    ->set([
                        'Item' => $item
                    ])
                    ->where('Id', $data['id'])
                    ->where('IdArticle', $data['idArticle']) // Security check
                    ->execute();

                $message = 'Élément mis à jour avec succès';
            } else {
                $this->fluent->insertInto('Carousel')
                    ->values([
                        'IdArticle' => $data['idArticle'],
                        'Item' => $item
                    ])
                    ->execute();

                $message = 'Élément ajouté avec succès';
            }

            $this->jsonResponse(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        $person = $this->getPerson();
        if (!$person) {
            $this->jsonResponse(['error' => 'Utilisateur non connecté'], 401);
            return;
        }

        $item = $this->fluent->from('Carousel')
            ->select('Carousel.*')
            ->leftJoin('Article ON Article.Id = Carousel.IdArticle')
            ->where('Carousel.Id', $id)
            ->fetch();

        if (!$item) {
            $this->jsonResponse(['error' => 'Élément non trouvé'], 404);
            return;
        }

        $article = $this->getArticle($item->IdArticle);
        if (!$article || $article->CreatedBy != $person->Id) {
            $this->jsonResponse(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }

        try {
            $this->fluent->deleteFrom('Carousel')
                ->where('Id', $id)
                ->execute();

            $this->jsonResponse(['success' => true, 'message' => 'Élément supprimé avec succès']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }


    private function getArticle($id)
    {
        return $this->fluent->from('Article')->where('Id', $id)->fetch();
    }

    private function canAccessArticle($article, $person)
    {
        if ($article->OnlyForMembers && !$person) {
            return false;
        }

        if (!$article->PublishedBy && (!$person || $person->Id != $article->CreatedBy)) {
            return false;
        }

        if ($article->IdGroup && $person) {
            $userGroups = $this->authorizations->getUserGroups($person->Email);
            if (!in_array($article->IdGroup, $userGroups)) {
                return false;
            }
        }

        return true;
    }


    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    private function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        // Then remove potentially dangerous attributes
        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }
}