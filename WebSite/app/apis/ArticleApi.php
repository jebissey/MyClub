<?php

declare(strict_types=1);

namespace app\apis;

use Exception;
use Throwable;

use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\ArticleDataHelper;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\OrderReplyDataHelper;
use app\models\PersonDataHelper;
use app\models\ReplyDataHelper;

class ArticleApi extends AbstractApi
{
    private OrderReplyDataHelper $orderReplyDataHelper;
    private ReplyDataHelper $replyDataHelper;

    public function __construct(
        Application $application,
        ConnectedUser $connectedUser,
        DataHelper $dataHelper,
        PersonDataHelper $personDataHelper,
        private DesignDataHelper $designDataHelper,
        private ArticleDataHelper $articleDataHelper
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
        $this->replyDataHelper = new ReplyDataHelper($application);
        $this->orderReplyDataHelper = new OrderReplyDataHelper($application);
    }

    public function designVote(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $this->designDataHelper->insertOrUpdate(json_decode(file_get_contents('php://input'), true), $this->application->getConnectedUser()->person->Id);
        $this->renderJsonOK();
    }

    public function getAuthor(int $articleId): void
    {
        $result = $this->articleDataHelper->getAuthor($articleId);
        if ($result === false) $this->renderJsonBadRequest("Unknown article {$articleId}", __FILE__, __LINE__);
        $this->renderJsonOk(['author' => $result ? [$result] : []]);
    }

    public function saveOrderReply(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            $this->renderJsonBadRequest('Missing data', __FILE__, __LINE__);
            return;
        }
        $this->orderReplyDataHelper->insertOrUpdate((int)$person->Id, (int)$orderId, isset($data['order_answers']) ? json_encode($data['order_answers']) : '[]');
        $this->renderJsonOk();
    }

    public function saveSurveyReply(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $surveyId = $data['survey_id'] ?? null;
        if (!$surveyId) {
            $this->renderJsonBadRequest('Missing data', __FILE__, __LINE__);
            return;
        }
        $this->replyDataHelper->insertOrUpdate($person->Id, (int)$surveyId, isset($data['survey_answers']) ? json_encode($data['survey_answers']) : '[]');
        $this->renderJsonOk();
    }

    public function showOrderReplyForm(int $articleId): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person === false) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $order = $this->dataHelper->get('Order', ['IdArticle' => $articleId], 'Id, Question, Options');
        if (!$order) {
            $this->renderJsonBadRequest("Aucune commande trouvée pour l'article {$articleId}", __FILE__, __LINE__);
            return;
        }
        try {
            $options = json_decode($order->Options);
            if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("JSON error: " . json_last_error_msg());
            $previousReply = $this->dataHelper->get('OrderReply', ['IdOrder' => $order->Id, 'IdPerson' => $person->Id]);
            $previousAnswers = $previousReply ? json_decode($previousReply->Answers, true) : null;
            $this->renderJsonOk([
                'order' => [
                    'id' => $order->Id,
                    'question' => $order->Question,
                    'options' => $options,
                    'previousAnswers' => $previousAnswers
                ]
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }

    public function showSurveyReplyForm(int $articleId): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person === false) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $survey = $this->dataHelper->get('Survey', ['IdArticle' => $articleId], 'Id, Question, Options');
        if (!$survey) {
            $this->renderJsonBadRequest("Aucun sondage trouvé pour l'article {$articleId}", __FILE__, __LINE__);
            return;
        }
        try {
            $options = json_decode($survey->Options);
            if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("JSON error: " . json_last_error_msg());
            $previousReply = $this->dataHelper->get('Reply', ['IdSurvey' => $survey->Id, 'IdPerson' => $person->Id]);
            $previousAnswers = $previousReply ? json_decode($previousReply->Answers, true) : null;
            $this->renderJsonOk([
                'survey' => [
                    'id' => $survey->Id,
                    'question' => $survey->Question,
                    'options' => $options,
                    'previousAnswers' => $previousAnswers
                ]
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, $e->getFile(), $e->getLine());
        }
    }
}
