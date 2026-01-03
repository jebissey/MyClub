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
use PgSql\Lob;

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

    public function deleteItem(int $id): void
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
        if (!$this->authorizationDataHelper->getArticle($item->IdArticle, $this->application->getConnectedUser())) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $this->dataHelper->delete('Carousel', ['Id' => $id]);
            $this->renderJsonOk(['message' => 'Élément supprimé avec succès']);
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getItems(int $idArticle): void
    {
        try {
            $connectedUser = $this->application->getConnectedUser();
            if (!($connectedUser->person ?? false) || !$this->authorizationDataHelper->getArticle($idArticle, $connectedUser)) {
                $this->renderJsonForbidden(__FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk(['items' => $this->dataHelper->gets('Carousel', ['IdArticle' => $idArticle])]);
        } catch (QueryException $e) {
            $this->renderJsonBadRequest($e->getMessage(), $e->getFile(), $e->getLine());
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function saveItem(): void
    {
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['idArticle']) || !isset($data['item'])) {
            $this->renderJsonBadRequest("Données invalides", __FILE__, __LINE__);
            return;
        }
        if (!$this->authorizationDataHelper->getArticle($data['idArticle'], $connectedUser)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $item = WebApp::sanitizeHtml($data['item']);
        try {
            $message = $this->carouselDataHelper->set_($data, $item);
            $this->renderJsonOk(['message' => $message]);
        } catch (Throwable $e) {
            $this->renderJsonError('error' . $e->getMessage(),  ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
