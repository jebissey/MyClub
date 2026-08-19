<?php

declare(strict_types=1);

namespace app\apis;

use Exception;
use Throwable;
use app\enums\ApplicationError;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\helpers\To;
use app\helpers\WebApp;
use app\models\DataHelper;
use app\models\DesignDataHelper;
use app\models\OrderReplyDataHelper;
use app\models\PersonDataHelper;
use app\models\ReplyDataHelper;
use app\valueObjects\AnswersRow;
use app\valueObjects\QuestionRow;

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
    ) {
        parent::__construct($application, $connectedUser, $dataHelper, $personDataHelper);
        $this->replyDataHelper = new ReplyDataHelper($application);
        $this->orderReplyDataHelper = new OrderReplyDataHelper($application);
    }

    public function designVote(): void
    {
        if (!$this->application->getConnectedUser()->isRedactor()) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        if ($json === false) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }
        $raw = json_decode($json, true);
        if (!is_array($raw)) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }

        /** @var array{designId?: int|string, vote?: string} $data */
        $data = [];
        if (isset($raw['designId'])) {
            $data['designId'] = To::int($raw['designId']);
        }
        if (isset($raw['vote'])) {
            $data['vote'] = To::str($raw['vote']);
        }

        $this->designDataHelper->insertOrUpdate(
            $data,
            $this->application->getConnectedUser()->person->Id ?? 0
        );
        $this->renderJsonOK();
    }

    public function saveOrderReply(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        if ($json === false) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) {
            $this->renderJsonBadRequest('Missing data', __FILE__, __LINE__);
            return;
        }
        try {
            $orderAnswers = isset($data['order_answers'])
                ? json_encode($data['order_answers'], JSON_THROW_ON_ERROR)
                : '[]';
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, __FILE__, __LINE__);
            return;
        }
        $this->orderReplyDataHelper->insertOrUpdate(
            To::int($person->Id),
            To::int($orderId),
            $orderAnswers
        );
        $this->renderJsonOk();
    }

    public function saveSurveyReply(): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if (!$person) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $json = file_get_contents('php://input');
        if ($json === false) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->renderJsonBadRequest('Corps de requête invalide', __FILE__, __LINE__);
            return;
        }
        $surveyId = $data['survey_id'] ?? null;
        if (!$surveyId) {
            $this->renderJsonBadRequest('Missing data', __FILE__, __LINE__);
            return;
        }
        try {
            $surveyAnswers = isset($data['survey_answers'])
                ? json_encode($data['survey_answers'], JSON_THROW_ON_ERROR)
                : '[]';
        } catch (Throwable $e) {
            $this->renderJsonError($e->getMessage(), ApplicationError::Error->value, __FILE__, __LINE__);
            return;
        }
        $this->replyDataHelper->insertOrUpdate(
            $person->Id,
            To::int($surveyId),
            $surveyAnswers
        );
        $this->renderJsonOk();
    }

    public function showOrderReplyForm(int $articleId): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person === false) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $orderData = $this->dataHelper->get('Order', ['IdArticle' => $articleId], 'Id, Question, Options');
        if (!$orderData) {
            $this->renderJsonBadRequest("Aucune commande trouvée pour l'article {$articleId}", __FILE__, __LINE__);
            return;
        }
        /** @var object{Id: int|string, Question: string, Options: string} $orderData */
        $order = QuestionRow::fromStdClass($orderData);
        try {
            $options = json_decode($order->Options);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON error: " . json_last_error_msg());
            }
            $previousReplyData = $this->dataHelper->get('OrderReply', ['IdOrder' => $order->Id, 'IdPerson' => $person->Id]);
            $previousAnswers = null;
            if ($previousReplyData) {
                /** @var object{Answers: string} $previousReplyData */
                $previousAnswers = json_decode(AnswersRow::fromStdClass($previousReplyData)->Answers, true);
            }
            $this->renderJsonOk([
                'order' => [
                    'id' => $order->Id,
                    'question' => $order->Question,
                    'options' => $options,
                    'previousAnswers' => $previousAnswers
                ]
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function showSurveyReplyForm(int $articleId): void
    {
        $person = $this->application->getConnectedUser()->person ?? false;
        if ($person === false) {
            $this->renderJsonForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->renderJsonMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $surveyData = $this->dataHelper->get('Survey', ['IdArticle' => $articleId], 'Id, Question, Options');
        if (!$surveyData) {
            $this->renderJsonBadRequest("Aucun sondage trouvé pour l'article {$articleId}", __FILE__, __LINE__);
            return;
        }
        /** @var object{Id: int|string, Question: string, Options: string} $surveyData */
        $survey = QuestionRow::fromStdClass($surveyData);
        try {
            $options = json_decode($survey->Options);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON error: " . json_last_error_msg());
            }
            $previousReplyData = $this->dataHelper->get('Reply', ['IdSurvey' => $survey->Id, 'IdPerson' => $person->Id]);
            $previousAnswers = null;
            if ($previousReplyData) {
                /** @var object{Answers: string} $previousReplyData */
                $previousAnswers = json_decode(AnswersRow::fromStdClass($previousReplyData)->Answers, true);
            }
            $this->renderJsonOk([
                'survey' => [
                    'id' => $survey->Id,
                    'question' => $survey->Question,
                    'options' => $options,
                    'previousAnswers' => $previousAnswers
                ]
            ]);
        } catch (Throwable $e) {
            $this->renderJsonError(
                $e->getMessage(),
                ApplicationError::Error->value,
                $e->getFile(),
                $e->getLine()
            );
        }
    }
}
