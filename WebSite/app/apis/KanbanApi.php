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

    /**
     * GET /api/kanban/cards
     * Récupère toutes les cartes de l'utilisateur connecté
     */
    public function getCards(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $personId = $this->connectedUser->person->getId();
        $cards = $this->kanbanDataHelper->getKanbanCards($personId);

        $this->renderJson(['cards' => $cards], true, ApplicationError::Ok->value);
    }

    public function getCard(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $personId = $this->connectedUser->person->getId();
        $card = $this->kanbanDataHelper->getKanbanCard($id, $personId);

        if (!$card) {
            $this->renderJson(['error' => 'Card not found'], false, ApplicationError::PageNotFound->value);
            return;
        }

        $this->renderJson(['card' => $card], true, ApplicationError::Ok->value);
    }

    /**
     * POST /api/kanban/card/create
     * Crée une nouvelle carte
     */
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
            $this->renderJson(['error' => 'Invalid JSON'], false, ApplicationError::BadRequest->value);
            return;
        }

        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');

        if (empty($title)) {
            $this->renderJson(['error' => 'Title is required'], false, ApplicationError::BadRequest->value);
            return;
        }

        try {
            $personId = $this->connectedUser->person->getId();
            $kanbanId = $this->kanbanDataHelper->createKanbanCard($personId, $title, $detail);

            $this->renderJson([
                'id' => $kanbanId,
                'message' => 'Card created successfully'
            ], true, ApplicationError::Ok->value);
        } catch (Throwable $e) {
            $this->renderJson([
                'error' => 'Failed to create card',
                'details' => $e->getMessage()
            ], false, ApplicationError::Error->value);
        }
    }

    /**
     * POST /api/kanban/card/update
     * Met à jour une carte existante
     */
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
            $this->renderJson(['error' => 'Invalid JSON'], false, ApplicationError::BadRequest->value);
            return;
        }

        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $detail = trim($data['detail'] ?? '');

        if ($id <= 0) {
            $this->renderJson(['error' => 'Invalid card ID'], false, ApplicationError::BadRequest->value);
            return;
        }

        if (empty($title)) {
            $this->renderJson(['error' => 'Title is required'], false, ApplicationError::BadRequest->value);
            return;
        }

        try {
            $personId = $this->connectedUser->person->getId();
            $success = $this->kanbanDataHelper->updateKanbanCard($id, $personId, $title, $detail);

            if ($success) {
                $this->renderJson(['message' => 'Card updated successfully'], true, ApplicationError::Ok->value);
            } else {
                $this->renderJson(['error' => 'Card not found or unauthorized'], false, ApplicationError::PageNotFound->value);
            }
        } catch (Throwable $e) {
            $this->renderJson([
                'error' => 'Failed to update card',
                'details' => $e->getMessage()
            ], false, ApplicationError::Error->value);
        }
    }

    /**
     * POST /api/kanban/card/move
     * Déplace une carte vers un nouveau statut
     */
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
            $this->renderJson(['error' => 'Invalid JSON'], false, ApplicationError::BadRequest->value);
            return;
        }

        $id = (int)($data['id'] ?? 0);
        $newStatus = $data['status'] ?? '';
        $changeType = $data['changeType'] ?? '';
        $remark = trim($data['remark'] ?? '');

        if ($id <= 0) {
            $this->renderJson(['error' => 'Invalid card ID'], false, ApplicationError::BadRequest->value);
            return;
        }

        $validStatuses = ['💡', '☑️', '🔧', '🏁'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->renderJson(['error' => 'Invalid status'], false, ApplicationError::BadRequest->value);
            return;
        }

        if (empty($changeType)) {
            $this->renderJson(['error' => 'Change type is required'], false, ApplicationError::BadRequest->value);
            return;
        }

        try {
            $personId = $this->connectedUser->person->getId();
            $success = $this->kanbanDataHelper->moveKanbanCard($id, $personId, $newStatus, $changeType, $remark);

            if ($success) {
                $this->renderJson(['message' => 'Card moved successfully'], true, ApplicationError::Ok->value);
            } else {
                $this->renderJson(['error' => 'Card not found or unauthorized'], false, ApplicationError::PageNotFound->value);
            }
        } catch (Throwable $e) {
            $this->renderJson([
                'error' => 'Failed to move card',
                'details' => $e->getMessage()
            ], false, ApplicationError::Error->value);
        }
    }

    /**
     * POST /api/kanban/card/delete
     * Supprime une carte
     */
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
            $this->renderJson(['error' => 'Invalid JSON'], false, ApplicationError::BadRequest->value);
            return;
        }

        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            $this->renderJson(['error' => 'Invalid card ID'], false, ApplicationError::BadRequest->value);
            return;
        }

        try {
            $personId = $this->connectedUser->person->getId();
            $success = $this->kanbanDataHelper->deleteKanbanCard($id, $personId);

            if ($success) {
                $this->renderJson(['message' => 'Card deleted successfully'], true, ApplicationError::Ok->value);
            } else {
                $this->renderJson(['error' => 'Card not found or unauthorized'], false, ApplicationError::PageNotFound->value);
            }
        } catch (Throwable $e) {
            $this->renderJson([
                'error' => 'Failed to delete card',
                'details' => $e->getMessage()
            ], false, ApplicationError::Error->value);
        }
    }

    /**
     * GET /api/kanban/card/@id/history
     * Récupère l'historique d'une carte
     */
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

        $personId = $this->connectedUser->person->getId();
        $card = $this->kanbanDataHelper->getKanbanCard($id, $personId);

        if (!$card) {
            $this->renderJson(['error' => 'Card not found'], false, ApplicationError::PageNotFound->value);
            return;
        }

        $history = $this->kanbanDataHelper->getKanbanHistory($id);

        $this->renderJson([
            'card' => $card,
            'history' => $history
        ], true, ApplicationError::Ok->value);
    }

    public function getStats(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        if (!$this->connectedUser->isKanbanDesigner()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }

        $personId = $this->connectedUser->person->getId();
        $stats = $this->kanbanDataHelper->getKanbanStats($personId);

        $this->renderJson(['stats' => $stats], true, ApplicationError::Ok->value);
    }
}
