<?php

declare(strict_types=1);

namespace app\apis;

use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\DataHelper;
use app\models\kanbanDataHelper;
use app\models\PersonDataHelper;

class KanbanApi extends AbstractApi
{
    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private KanbanDataHelper $kanbanDataHelper,
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
    }

    #region Card
    public function createCard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $kanbanCardId = (int)trim($data['cardType'] ?? '');
        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');
        if (empty($kanbanCardId)) {
            $this->renderJsonBadRequest('CardType Id is required', __FILE__, __LINE__);
            return;
        }
        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanId = $this->kanbanDataHelper->createKanbanCard($kanbanCardId, $title, $detail);
            $this->renderJsonOk([
                'id' => $kanbanId,
                'message' => 'Card created successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to create card: ' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deleteCard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->deleteKanbanCard($id, $this->connectedUser->person->Id);
            if ($success) $this->renderJsonOk([], 'Card deleted successfully');
            else          $this->renderJsonBadRequest('Card not found or unauthorized', __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to delete card' .  $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getHistory(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $this->renderJsonOk([
            'history' => $this->kanbanDataHelper->getKanbanHistory($id)
        ]);
    }

    public function moveCard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = (int)($data['idKanbanCard'] ?? 0);
        $what = $data['what'] ?? '';
        $remark = $data['remark'] ?? '';
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        if (empty($what)) {
            $this->renderJsonBadRequest('What is required', __FILE__, __LINE__);
            return;
        }

        $success = $this->kanbanDataHelper->moveKanbanCard($id, $what, $remark);
        if ($success) $this->renderJsonOk([], 'Card moved successfully');
        else          $this->renderJsonBadRequest('Card not found', __FILE__, __LINE__);
    }

    public function updateCard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');

        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }

        try {
            $success = $this->kanbanDataHelper->updateKanbanCard($id, $this->connectedUser->person->Id, $title, $detail);

            if ($success) $this->renderJsonOk([], 'Card updated successfully');
            else          $this->renderJsonBadRequest('Card not found or unauthorized', __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to update card :' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updateCardStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = (int)($data['idKanbanCardStatus'] ?? 0);
        $remark = trim($data['remark'] ?? '');
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card status Id', __FILE__, __LINE__);
            return;
        }

        try {
            $success = $this->kanbanDataHelper->updateKanbanCardStatus($id, $this->connectedUser->person->Id, $remark);

            if ($success) $this->renderJsonOk([], 'Card status updated successfully');
            else          $this->renderJsonBadRequest('Card status not found or unauthorized', __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to update card status :' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region Project
    public function createProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');

        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanProjectId = $this->kanbanDataHelper->createKanbanProject($this->connectedUser->person->Id, $title, $detail);

            $this->renderJsonOk([
                'id' => $kanbanProjectId,
                'message' => 'Project created successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to create project: ' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deleteProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = trim($data['id'] ?? '');
        if (empty($id)) {
            $this->renderJsonBadRequest('Id is required', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->deleteKanbanProject((int)$id, $this->connectedUser->person->Id);
            if ($success) $this->renderJsonOk();
            else          $this->renderJsonBadRequest('Project not found or unauthorized', __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to delete project: ' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getProject(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $kanbanProject = $this->kanbanDataHelper->getKanbanProject($id);
            if (!$kanbanProject) {
                $this->renderJsonBadRequest("Project {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk(['project' => $kanbanProject], 'Project loaded successfully');
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to get project : ' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function updateProject(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid project ID', __FILE__, __LINE__);
            return;
        }
        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->updateKanbanProject($id, $title, $detail, $this->connectedUser->person->Id);
            if ($success) $this->renderJsonOk([], 'Project updated successfully', ApplicationError::Ok->value);
            else          $this->renderJsonBadRequest('Project not found or unauthorized', __FILE__, __LINE__);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to update project' . $e->getMessage(),  ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    #region CardType
    public function createCardType(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $label = trim($data['label'] ?? '');
        $detail = trim($data['detail'] ?? '');
        $projectId = (int)($data['projectId'] ?? '');

        if (empty($label)) {
            $this->renderJsonBadRequest('Label is required', __FILE__, __LINE__);
            return;
        }
        if (empty($projectId)) {
            $this->renderJsonBadRequest('ProjectId is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanCardTypeId = $this->kanbanDataHelper->createKanbanCardType($projectId, $label, $detail);
            $this->renderJsonOk([
                'id' => $kanbanCardTypeId,
                'message' => 'KabanCardType created successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to create project' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function deleteCardType(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $id = (int)($data['id'] ?? '');

        if (empty($id)) {
            $this->renderJsonBadRequest('Id is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanCardTypeId = $this->kanbanDataHelper->deleteKanbanCardType($id);
            $this->renderJsonOk([
                'id' => $kanbanCardTypeId,
                'message' => 'KabanCardType deleted successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError('Failed to delete cardType' . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getProjectCards(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $query = $this->application->getFlight()->request()->query;
            $ct = filter_var($query['ct'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            $title  = $query['title']  ?? null;
            $detail = $query['detail'] ?? null;
            $this->renderJsonOk(['cards' => $this->kanbanDataHelper->getProjectCards($id, $ct, $title, $detail)]);
        } catch (Throwable $e) {
            $this->renderJsonError("Failed to get project's cards : " . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function getProjectCardTypes(int $id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $this->renderJsonOk(['cardTypes' => $this->kanbanDataHelper->getProjectCardTypes($id)]);
        } catch (Throwable $e) {
            $this->renderJsonError("Failed to get project's card types : " . $e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
