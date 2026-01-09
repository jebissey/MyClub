<?php
declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\NeedDataHelper;
use app\models\NeedTypeDataHelper;
use app\models\PersonDataHelper;
use app\valueObjects\ApiResponse;

class EventNeedTypeApi extends AbstractApi
{
    public function __construct(
        Application $application,
        private NeedDataHelper $needDataHelper,
        private NeedTypeDataHelper $needTypeDataHelper,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper
    ) {
        parent::__construct($application, $connectedUser,$dataHelper, $personDataHelper);
    }

    public function deleteNeedType(int $id): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        try {
            $apiResponse = $this->deleteNeedType_($id);
            $this->renderJson([], $apiResponse->success, $apiResponse->responseCode);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function saveNeedType(): void
    {
        if (!$this->application->getConnectedUser()->isEventDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $data = $this->getJsonInput();
        $name = $data['name'] ?? '';
        if ($name === '') {
            $this->renderJsonError('Missing parameter name', ApplicationError::BadRequest->value, __FILE__, __LINE__);
            return;
        }
        try {
            $this->renderJson(['Id' => $this->needTypeDataHelper->insertOrUpdate((int)$data['id'], $name)], true,  ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getNeedsByNeedType(int $id): void
    {
        try {
            $this->renderJsonOk([$this->needDataHelper->needsforNeedType($id)]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Private functions
    private function deleteNeedType_(int $id): ApiResponse
    {
        $countNeeds = $this->needDataHelper->countForNeedType($id);
        if ($countNeeds > 0) return new ApiResponse(false, ApplicationError::BadRequest->value, [], 'Ce type de besoin est associé à ' . $countNeeds . ' besoin(s) et ne peut pas être supprimé');
        return new ApiResponse(true, ApplicationError::Ok->value, ['result' => $this->needTypeDataHelper->delete_($id)]);
    }
}
