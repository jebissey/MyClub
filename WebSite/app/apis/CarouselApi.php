<?php

namespace app\apis;

use app\helpers\AuthorizationDataHelper;
use Throwable;

use app\helpers\Application;
use app\helpers\CarouselDataHelper;
use app\helpers\DataHelper;
use app\helpers\WebApp;

class CarouselApi extends AbstractApi
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getItems($idArticle)
    {
        $connectedUser = $this->connectedUser->get();
        if (!($connectedUser->person ?? false) || !(new AuthorizationDataHelper($this->application))->getArticle($idArticle, $connectedUser)) {
            $this->renderJson(['error' => 'Accès non autorisé'], 403);
            return;
        }
        $items = $this->dataHelper->gets('Carousel', ['IdArticle' => $idArticle]);
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
        if (!(new AuthorizationDataHelper($this->application))->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }
        $item = WebApp::sanitizeHtml($data['item']);
        try {
            $message = (new CarouselDataHelper($this->application))->set_($data, $item);
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
        $item = (new DataHelper($this->application))->get('Carousel', ['Id' => $id], 'IdArticle');
        if (!$item) {
            $this->renderJson(['error' => 'Élément non trouvé'], 404);
            return;
        }
        if (!(new AuthorizationDataHelper($this->application))->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], 403);
            return;
        }
        try {
            (new DataHelper($this->application))->delete('Carousel', ['Id' => $id]);
            $this->renderJson(['success' => true, 'message' => 'Élément supprimé avec succès']);
        } catch (Throwable $e) {
            $this->renderJson(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
