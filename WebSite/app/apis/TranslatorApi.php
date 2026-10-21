<?php

declare(strict_types=1);

namespace app\apis;

use JsonException;
use Throwable;
use app\exceptions\QueryException;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\PersonDataHelper;
use app\models\LanguagesDataHelper;

final class TranslatorApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        PersonDataHelper $personDataHelper,
        protected LanguagesDataHelper $languagesDataHelper
    ) {
        parent::__construct($application, $connectedUser, $personDataHelper, $languagesDataHelper);
    }

    public function save(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('POST', fn($u) => $u->isTranslator(), __FILE__, __LINE__)) {
            return;
        }

        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest("Invalid data", __FILE__, __LINE__);
            return;
        }
        if (
            !$data ||
            !isset($data['id']) ||
            !isset($data['lang']) ||
            !isset($data['value'])
        ) {
            $this->renderJsonBadRequest("Invalid data", __FILE__, __LINE__);
            return;
        }

        $id   = To::int($data['id']);
        $lang = To::str($data['lang']);
        $value = WebApp::sanitizeHtml(To::str($data['value']));

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
            $success = $this->languagesDataHelper->updateTranslation($id, $lang, $value);
            if (!$success) {
                $this->renderJsonError(
                    "Échec mise à jour",
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
                $e->getFile(),
                $e->getLine()
            );
        }
    }
}
