<?php

namespace app\apis;

use app\helpers\AuthorizationDataHelper;
use Throwable;

use app\helpers\Application;
use app\helpers\CarouselDataHelper;
use app\helpers\WebApp;

class CarouselApi extends BaseApi
{
    private AuthorizationDataHelper $authorizationDataHelper;
    private CarouselDataHelper $carouselDataHelper;

    public function __construct(Application $application)
    {
        parent::__construct($application);
        $this->authorizationDataHelper = new AuthorizationDataHelper($application);
        $this->carouselDataHelper = new CarouselDataHelper($this->application);
    }

    public function getItems($idArticle)
    {
        $person = $this->connectedUser->get()->person ?? false;
        if (!$person || !$this->authorizationDataHelper->getArticle($idArticle, $person)) {
            $this->renderJson(['error' => 'Accès non autorisé'], 403);
            return;
        }
        $items = $this->carouselDataHelper->getByArticle($idArticle);
        $this->renderJson(['success' => true, 'items' => $items]);
    }

    public function saveItem()
    {
        $person = $this->connectedUser->get()->person ?? false;
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJson(['error' => 'Données invalides'], 400);
            return;
        }
        if (!$this->authorizationDataHelper->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }

        $item = WebApp::sanitizeHtml($data['item']);
        try {
            $message = $this->carouselDataHelper->set_($data, $item);
            $this->renderJson(['success' => true, 'message' => $message]);
        } catch (Throwable $e) {
            $this->renderJson(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }

    public function deleteItem($id)
    {
        $person = $this->connectedUser->get()->person ?? false;
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], 401);
            return;
        }
        $item = $this->carouselDataHelper->get_($id);
        if (!$item) {
            $this->renderJson(['error' => 'Élément non trouvé'], 404);
            return;
        }
        if (!$this->authorizationDataHelper->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }
        try {
            $this->carouselDataHelper->delete_($id);
            $this->renderJson(['success' => true, 'message' => 'Élément supprimé avec succès']);
        } catch (Throwable $e) {
            $this->renderJson(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
