<?php

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\CarouselDataHelper;
use app\models\DataHelper;

class CarouselApi extends AbstractApi
{

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    public function getItems($idArticle)
    {
        try {
            $connectedUser = $this->connectedUser->get();
            if (!($connectedUser->person ?? false) || !(new AuthorizationDataHelper($this->application))->getArticle($idArticle, $connectedUser)) {
                $this->renderJson(['error' => 'Accès non autorisé'], false, ApplicationError::Forbidden->value);
                return;
            }
            $items = $this->dataHelper->gets('Carousel', ['IdArticle' => $idArticle]);
            $this->renderJson(['items' => $items], true, ApplicationError::Ok->value);
        } catch (QueryException $e) {
            $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::BadRequest->value);
        } catch (Throwable $e) {
            $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::Error->value);
        }
    }

    public function saveItem()
    {
        $person = $this->connectedUser->get()->person ?? false;
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], false, ApplicationError::Forbidden->value);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJson(['error' => 'Données invalides'], false, ApplicationError::BadRequest->value);
            return;
        }
        if (!(new AuthorizationDataHelper($this->application))->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], false, ApplicationError::Forbidden->value);
            return;
        }
        $item = WebApp::sanitizeHtml($data['item']);
        try {
            $message = (new CarouselDataHelper($this->application))->set_($data, $item);
            $this->renderJson(['message' => $message], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJson(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], false, ApplicationError::Error->value);
        }
    }

    public function deleteItem($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $person = $this->connectedUser->get()->person ?? false;
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], false, ApplicationError::Unauthorized->value);
            return;
        }
        $item = (new DataHelper($this->application))->get('Carousel', ['Id' => $id], 'IdArticle');
        if (!$item) {
            $this->renderJson(['error' => 'Élément non trouvé'], false, ApplicationError::PageNotFound->value);
            return;
        }
        if (!(new AuthorizationDataHelper($this->application))->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], false, ApplicationError::Forbidden->value);
            return;
        }
        try {
            (new DataHelper($this->application))->delete('Carousel', ['Id' => $id]);
            $this->renderJson(['message' => 'Élément supprimé avec succès'], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::Error->value);
        }
    }
}
