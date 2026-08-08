<?php

declare(strict_types=1);

namespace app\apis;

use JsonException;
use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\KanbanDataHelper;
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
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $kanbanCardId = (int)trim(WebApp::toStr($data['cardType'] ?? ''));
        $title = trim(WebApp::toStr($data['title'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));
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
            $this->renderJsonError(
                'Failed to create card: ' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function deleteCard(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = WebApp::toInt($data['id'] ?? 0);
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->deleteKanbanCard($id, $this->connectedUser->person->Id ?? 0);
            if ($success) {
                $this->renderJsonOk([], 'Card deleted successfully');
            } else {
                $this->renderJsonBadRequest('Card not found or unauthorized', __FILE__, __LINE__);
            }
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to delete card' .  $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function getHistory(int $id): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
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
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = WebApp::toInt($data['idKanbanCard'] ?? 0);
        $what = WebApp::toStr($data['what'] ?? '');
        $remark = WebApp::toStr($data['remark'] ?? '');
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        if (empty($what)) {
            $this->renderJsonBadRequest('What is required', __FILE__, __LINE__);
            return;
        }

        $success = $this->kanbanDataHelper->moveKanbanCard($id, $what, $remark);
        if ($success) {
            $this->renderJsonOk([], 'Card moved successfully');
        } else {
            $this->renderJsonBadRequest('Card not found', __FILE__, __LINE__);
        }
    }

    public function updateCard(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = WebApp::toInt($data['id'] ?? 0);
        $title = trim(WebApp::toStr($data['title'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));

        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card ID', __FILE__, __LINE__);
            return;
        }
        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }

        try {
            $success = $this->kanbanDataHelper->updateKanbanCard($id, $this->connectedUser->person->Id ?? 0, $title, $detail);

            if ($success) {
                $this->renderJsonOk([], 'Card updated successfully');
            } else {
                $this->renderJsonBadRequest('Card not found or unauthorized', __FILE__, __LINE__);
            }
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to update card :' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function updateCardStatus(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = WebApp::toInt($data['idKanbanCardStatus'] ?? 0);
        $remark = trim(WebApp::toStr($data['remark'] ?? ''));
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid card status Id', __FILE__, __LINE__);
            return;
        }

        try {
            $success = $this->kanbanDataHelper->updateKanbanCardStatus($id, $this->connectedUser->person->Id ?? 0, $remark);

            if ($success) {
                $this->renderJsonOk([], 'Card status updated successfully');
            } else {
                $this->renderJsonBadRequest('Card status not found or unauthorized', __FILE__, __LINE__);
            }
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to update card status :' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    #region Project
    public function createProject(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $title = trim(WebApp::toStr($data['title'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));

        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanProjectId = $this->kanbanDataHelper->createKanbanProject($this->connectedUser->person->Id ?? 0, $title, $detail);

            $this->renderJsonOk([
                'id' => $kanbanProjectId,
                'message' => 'Project created successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to create project: ' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function deleteProject(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = trim(WebApp::toStr($data['id'] ?? ''));
        if (empty($id)) {
            $this->renderJsonBadRequest('Id is required', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->deleteKanbanProject((int)$id, $this->connectedUser->person->Id ?? 0);
            if ($success) {
                $this->renderJsonOk();
            } else {
                $this->renderJsonBadRequest('Project not found or unauthorized', __FILE__, __LINE__);
            }
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to delete project: ' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function getProject(int $id): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        try {
            $kanbanProject = $this->kanbanDataHelper->getKanbanProject($id);
            if ($kanbanProject === false) {
                $this->renderJsonBadRequest("Project {$id} not found", __FILE__, __LINE__);
                return;
            }
            $this->renderJsonOk(['project' => $kanbanProject], 'Project loaded successfully');
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to get project : ' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function updateProject(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }
        $id = WebApp::toInt($data['id'] ?? 0);
        $title = trim(WebApp::toStr($data['title'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));
        if ($id <= 0) {
            $this->renderJsonBadRequest('Invalid project ID', __FILE__, __LINE__);
            return;
        }
        if (empty($title)) {
            $this->renderJsonBadRequest('Title is required', __FILE__, __LINE__);
            return;
        }
        try {
            $success = $this->kanbanDataHelper->updateKanbanProject($id, $title, $detail, $this->connectedUser->person->Id ?? 0);
            if ($success) {
                $this->renderJsonOk([], 'Project updated successfully');
            } else {
                $this->renderJsonBadRequest('Project not found or unauthorized', __FILE__, __LINE__);
            }
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to update project' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    #region CardType
    public function createCardType(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $label = trim(WebApp::toStr($data['label'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));
        $color = trim(WebApp::toStr($data['color'] ?? ''));
        $projectId = WebApp::toInt($data['projectId'] ?? 0);

        if (empty($label)) {
            $this->renderJsonBadRequest('Label is required', __FILE__, __LINE__);
            return;
        }
        if (empty($projectId)) {
            $this->renderJsonBadRequest('ProjectId is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanCardTypeId = $this->kanbanDataHelper->createKanbanCardType($projectId, $label, $detail, $color);
            $this->renderJsonOk([
                'id' => $kanbanCardTypeId,
                'message' => 'KabanCardType created successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to create project' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function deleteCardType(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $id = WebApp::toInt($data['id'] ?? 0);

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
            $this->renderJsonError(
                'Failed to delete cardType' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function getProjectCards(int $id): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
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
            $title  = isset($query['title']) && is_string($query['title']) ? $query['title'] : null;
            $detail = isset($query['detail']) && is_string($query['detail']) ? $query['detail'] : null;
            $this->renderJsonOk(['cards' => $this->kanbanDataHelper->getProjectCards($id, $ct, $title, $detail)]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                "Failed to get project's cards : " . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function getProjectCardTypes(int $id): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
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
            $this->renderJsonError(
                "Failed to get project's card types : " . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function updateCardType(): void
    {
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        try {
            $data = $this->getJsonInput();
        } catch (JsonException $e) {
            $this->renderJsonBadRequest('Invalid JSON', __FILE__, __LINE__);
            return;
        }

        $label = trim(WebApp::toStr($data['label'] ?? ''));
        $detail = trim(WebApp::toStr($data['detail'] ?? ''));
        $color = trim(WebApp::toStr($data['color'] ?? ''));
        $id = WebApp::toInt($data['id'] ?? 0);

        if (empty($label)) {
            $this->renderJsonBadRequest('Label is required', __FILE__, __LINE__);
            return;
        }
        if (empty($id)) {
            $this->renderJsonBadRequest('Id is required', __FILE__, __LINE__);
            return;
        }

        try {
            $kanbanCardTypeId = $this->kanbanDataHelper->updateKanbanCardType($id, $label, $detail, $color);
            $this->renderJsonOk([
                'id' => $kanbanCardTypeId,
                'message' => 'KabanCardType updated successfully'
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                'Failed to update cardType' . $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }
}
