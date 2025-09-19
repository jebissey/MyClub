<?php
declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\AuthorizationDataHelper;
use app\models\CarouselDataHelper;
use app\models\DataHelper;
use app\models\PersonDataHelper;

class CarouselApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private AuthorizationDataHelper $authorizationDataHelper,
        private CarouselDataHelper $carouselDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    public function getItems($idArticle)
    {
        try {
            $connectedUser = $this->application->getConnectedUser();
            if (!($connectedUser->person ?? false) || !$this->authorizationDataHelper->getArticle($idArticle, $connectedUser)) {
                $this->renderJson(['error' => 'Accès non autorisé'], false, ApplicationError::Forbidden->value);
                return;
            }
            $items = $this->dataHelper->gets('Carousel', ['IdArticle' => $idArticle]);
            $this->renderJson(['items' => $items], true, ApplicationError::Ok->value);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::Error->value);
        }
    }

    public function saveItem()
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJson(['error' => 'Utilisateur non connecté'], false, ApplicationError::Forbidden->value);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJsonBadRequest("Données invalides", __FILE__, __LINE__);
            return;
        }
        if (!$this->authorizationDataHelper->getArticle($data['idArticle'], $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], false, ApplicationError::Forbidden->value);
            return;
        }
        $item = WebApp::sanitizeHtml($data['item']);
        try {
            $message = $this->carouselDataHelper->set_($data, $item);
            $this->renderJson(['message' => $message], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJson(['error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], false, ApplicationError::Error->value);
        }
    }

    public function deleteItem($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $item = $this->dataHelper->get('Carousel', ['Id' => $id], 'IdArticle');
        if (!$item) {
            $this->renderJsonBadRequest("Item {$id} not found", __FILE__, __LINE__);
            return;
        }
        $person = $this->application->getConnectedUser()->person;
        if (!$this->authorizationDataHelper->getArticle($item->IdArticle, $person)) {
            $this->renderJson(['error' => 'Vous n\'êtes pas autorisé à modifier cet article'], false, ApplicationError::Forbidden->value);
            return;
        }
        try {
            $this->dataHelper->delete('Carousel', ['Id' => $id]);
            $this->renderJson(['message' => 'Élément supprimé avec succès'], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJson(['error' => $e->getMessage()], false, ApplicationError::Error->value);
        }
    }
}
