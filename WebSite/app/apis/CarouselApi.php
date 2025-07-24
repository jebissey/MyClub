<?php

namespace app\apis;

use Exception;

use app\helpers\carouselHelper;
use app\utils\Webapp;

class CarouselApi extends BaseApi
{
    private CarouselHelper $carouselHelper;

    public function __construct()
    {
        $this->carouselHelper = new carouselHelper();
    }

    public function getItems($idArticle)
    {
        $person = $this->personDataHelper->getPerson();
        if (!$this->application->getAuthorizations()->getArticle($idArticle, $person)) {
            $this->renderJson(['error' => 'Accès non autorisé'], 403);
            return;
        }
        $items = $this->carouselHelper->getByArticle($idArticle);
        $this->renderJson(['success' => true, 'items' => $items]);
    }

    public function saveItem()
    {
        $person = $this->personDataHelper->getPerson();
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJson(['error' => 'Données invalides'], 400);
            return;
        }
        if (!$this->application->getAuthorizations()->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }

        $item = Webapp::sanitizeHtml($data['item']);
        try {
            $message = $this->carouselHelper->set_($data, $item);
            $this->renderJson(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            $this->renderJson(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        $person = $this->personDataHelper->getPerson();
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $item = $this->carouselHelper->get_($id);
        if (!$item) {
            $this->renderJson(['error' => 'Élément non trouvé'], 404);
            return;
        }
        if (!$this->application->getAuthorizations()->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }
        try {
            $this->carouselHelper->delete_($id);
            $this->renderJson(['success' => true, 'message' => 'Élément supprimé avec succès']);
        } catch (\Exception $e) {
            $this->renderJson(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
