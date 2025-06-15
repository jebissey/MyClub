<?php

namespace app\api;

use Exception;
use app\controllers\BaseController;

class CarouselApi extends BaseController
{
    public function getItems($idArticle)
    {
        $person = $this->getPerson();
        if (!$this->authorizations->getArticle($idArticle, $person)) {
            $this->renderJson(['error' => 'Accès non autorisé'], 403);
            return;
        }
        $items = $this->fluent->from('Carousel')->where('IdArticle', $idArticle)->fetchAll();
        $this->renderJson(['success' => true, 'items' => $items]);
    }

    public function saveItem()
    {
        $person = $this->getPerson();
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJson(['error' => 'Données invalides'], 400);
            return;
        }
        if (!$this->authorizations->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }

        $item = $this->sanitizeHtml($data['item']);
        try {
            if (!empty($data['id'])) {
                $this->fluent->update('Carousel')
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
            $this->renderJson(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            $this->renderJson(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        $person = $this->getPerson();
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $item = $this->fluent->from('Carousel')
            ->select('Carousel.*')
            ->leftJoin('Article ON Article.Id = Carousel.IdArticle')
            ->where('Carousel.Id', $id)
            ->fetch();
        if (!$item) {
            $this->renderJson(['error' => 'Élément non trouvé'], 404);
            return;
        }
        if (!$this->authorizations->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }
        try {
            $this->fluent->deleteFrom('Carousel')
                ->where('Id', $id)
                ->execute();
            $this->renderJson(['success' => true, 'message' => 'Élément supprimé avec succès']);
        } catch (\Exception $e) {
            $this->renderJson(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

    #region Private functions
    private function sanitizeHtml($html)
    {
        $allowed_tags = '<div><span><p><br><strong><em><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><blockquote><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed_tags);

        $html = preg_replace('/<(.*?)[\s|>]on[a-z]+=[\'"].*?[\'"]>(.*?)<\/\\1>/i', '<$1>$2</$1>', $html);
        $html = preg_replace('/javascript:.*?[\'"]/i', '', $html);

        return $html;
    }
}
