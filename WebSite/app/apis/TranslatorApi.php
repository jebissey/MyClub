<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\PersonDataHelper;
use app\models\LanguagesDataHelper;

class TranslatorApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private LanguagesDataHelper $languagesDataHelper
    ) {
        parent::__construct(
            $application,
            $connectedUser,
            $dataHelper,
            $personDataHelper
        );
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        if (!($connectedUser->isTranslator() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (
            !$data ||
            !isset($data['id']) ||
            !isset($data['lang']) ||
            !isset($data['value'])
        ) {
            $this->renderJsonBadRequest("Données invalides", __FILE__, __LINE__);
            return;
        }

        $id   = (int)$data['id'];
        $lang = (string)$data['lang'];
        $value = WebApp::sanitizeHtml((string)$data['value']);

        if ($id <= 0) {
            $this->renderJsonBadRequest("Id invalide", __FILE__, __LINE__);
            return;
        }

        $allowedLanguages = $this->languagesDataHelper->getAllowedLanguages();

        if (!in_array($lang, $allowedLanguages, true)) {
            $this->renderJsonBadRequest("Langue non autorisée", __FILE__, __LINE__);
            return;
        }

        try {

            $success = $this->languagesDataHelper
                ->updateTranslation($id, $lang, $value);

            if (!$success) {
                $this->renderJsonError(
                    "Échec mise à jour",
                    ApplicationError::Error->value,
                    __FILE__,
                    __LINE__
                );
                return;
            }

            $this->renderJsonOk([
                'message' => 'Traduction sauvegardée',
                'id' => $id,
                'lang' => $lang
            ]);

        } catch (QueryException $e) {

            $this->renderJsonBadRequest(
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

        } catch (Throwable $e) {

            $this->renderJsonError(
                'error ' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }
}