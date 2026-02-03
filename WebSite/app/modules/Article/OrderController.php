<?php

declare(strict_types=1);

namespace app\modules\Article;

use DateTime;

use app\enums\FilterInputRule;
use app\enums\OrderVisibility;
use app\exceptions\IntegrityException;
use app\helpers\Application;
use app\helpers\WebApp;
use app\models\OrderDataHelper;
use app\modules\Common\AbstractController;

class OrderController extends AbstractController
{
    public function __construct(Application $application, private OrderDataHelper $orderDataHelper)
    {
        parent::__construct($application);
    }

    public function add(int $articleId): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        $article = $this->dataHelper->get('Article', ['Id' => $articleId], 'Title, Id');
        if (!$article) {
            $this->redirect('/articles');
            return;
        }
        $this->render('Article/views/order_add.latte', $this->getAllParams([
            'article'  => $article,
            'order'    => $this->dataHelper->get('Order', ['IdArticle' => $article->Id], 'Question, Options, ClosingDate, Visibility'),
            'page'     => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function createOrUpdate(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor() ?? false)) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $schema = [
            'article_id'  => FilterInputRule::Int->value,
            'question'    => FilterInputRule::HtmlSafeText->value,
            'closingDate' => FilterInputRule::DateTime->value,
            'visibility'  => $this->application->enumToValues(OrderVisibility::class),
            'options'     => FilterInputRule::ArrayString->value,
        ];

        $input       = WebApp::filterInput($schema, $this->flight->request()->data->getData());
        $articleId   = $input['article_id']  ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        $question    = $input['question']    ?? '???';
        $closingDate = $input['closingDate'] ?? new DateTime('+7 days');
        $visibility  = $input['visibility']  ?? OrderVisibility::Redactor->value;

        $options = [];
        foreach ($input['options'] ?? [] as $option) {
            $options[] = str_replace('"', "''", $option);
        }
        $optionsJson = json_encode($options);

        $fields = [
            'Question'    => $question,
            'Options'     => $optionsJson,
            'ClosingDate' => $closingDate,
            'IdArticle'   => $articleId,
            'Visibility'  => $visibility,
        ];

        $order = $this->dataHelper->get('Order', ['IdArticle' => $articleId], 'Id');
        if ($order) $this->dataHelper->set('Order', $fields, ['Id' => $order->Id]);
        else        $this->dataHelper->set('Order', $fields);

        $this->redirect('/article/' . $articleId);
    }

    public function viewResults(int $articleId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }
        if ($this->dataHelper->get('Article', ['Id' => $articleId], 'Id') === false) {
            $this->raiseBadRequest("Article {$articleId} doesn't exist", __FILE__, __LINE__);
            return;
        }
        $connectedUser = $this->application->getConnectedUser();
        if ($connectedUser->person === null) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }

        $order = $this->orderDataHelper->getWithCreator($articleId);
        if (!$order) {
            $this->raiseBadRequest("No order for article {$articleId}", __FILE__, __LINE__);
            $this->redirect('/article/' . $articleId);
            return;
        }

        if ($this->authorizationDataHelper->canPersonReadOrderResults($this->dataHelper->get('Article', ['Id' => $order->IdArticle]), $connectedUser)) {
            $replies = $this->dataHelper->gets('OrderReply', ['IdOrder' => $order->Id]);

            $participants = [];
            $results      = [];
            $options      = json_decode($order->Options);
            foreach ($options as $option) {
                $results[$option] = 0;
            }
            foreach ($replies as $reply) {
                $answers = json_decode($reply->Answers);
                $person  = $this->dataHelper->get('Person', ['Id' => $reply->IdPerson], 'FirstName, LastName');

                $participants[] = [
                    'name'    => $person->FirstName . ' ' . $person->LastName,
                    'answers' => $answers,
                ];

                foreach ($answers as $article => $quantity) {
                    if (isset($results[$article])) $results[$article] += $quantity;
                }
            }
error_log("\n\n" . json_encode($participants, JSON_PRETTY_PRINT) . "\n");          

            $this->render('Article/views/order_results.latte', [
                'order'          => $order,
                'options'        => $options,
                'results'        => $results,
                'participants'   => $participants,
                'articleId'      => $articleId,
                'currentVersion' => Application::VERSION,
                'page'           => $connectedUser->getPage(),
            ]);
        } else {
            $this->raiseForbidden(__FILE__, __LINE__);
        }
    }
}