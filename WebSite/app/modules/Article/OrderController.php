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
use app\valueObjects\ArticleAuthorizationRow;
use app\valueObjects\ArticleRow;
use app\valueObjects\ArticleTitleRow;
use app\valueObjects\IdRow;
use app\valueObjects\OrderReplyRow;
use app\valueObjects\PersonNameRow;

class OrderController extends AbstractController
{
    public function __construct(Application $application, private OrderDataHelper $orderDataHelper)
    {
        parent::__construct($application);
    }

    public function add(int $articleId): void
    {
        if (!($this->application->getConnectedUser()->isRedactor())) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'GET') {
            $this->raiseMethodNotAllowed(__FILE__, __LINE__);
            return;
        }

        $articleData = $this->dataHelper->get('Article', ['Id' => $articleId], 'Title, Id');
        if (!$articleData) {
            $this->redirect('/articles');
            return;
        }

        /** @var object{Id: int|string, Title: string} $articleData */
        $article = ArticleTitleRow::fromStdClass($articleData);

        $this->render('Article/views/order_add.latte', $this->getAllParams([
            'article' => $article,
            'order'   => $this->dataHelper->get(
                'Order',
                ['IdArticle' => $article->Id],
                'Question, Options, ClosingDate, Visibility'
            ),
            'page' => $this->application->getConnectedUser()->getPage(),
        ]));
    }

    public function createOrUpdate(): void
    {
        if (!($this->application->getConnectedUser()->isRedactor())) {
            $this->raiseForbidden(__FILE__, __LINE__);
            return;
        }
        if (WebApp::getRequestMethod() !== 'POST') {
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
        /** @var int $articleId */
        $articleId   = $input['article_id']  ?? throw new IntegrityException('Fatal error in file ' . __FILE__ . ' at line ' . __LINE__);
        $question    = $input['question']    ?? '???';
        $closingDate = $input['closingDate'] ?? new DateTime('+7 days');
        $visibility  = $input['visibility']  ?? OrderVisibility::Redactor->value;

        /** @var array<string> $rawOptions */
        $rawOptions = $input['options'] ?? [];
        $options = [];
        foreach ($rawOptions as $option) {
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

        $orderData = $this->dataHelper->get('Order', ['IdArticle' => $articleId], 'Id');
        if ($orderData) {
            /** @var object{Id: int|string} $orderData */
            $order = IdRow::fromStdClass($orderData);
            $this->dataHelper->set('Order', $fields, ['Id' => $order->Id]);
        } else {
            $this->dataHelper->set('Order', $fields);
        }

        $this->redirect('/article/' . $articleId);
    }

    public function viewResults(int $articleId): void
    {
        if (WebApp::getRequestMethod() !== 'GET') {
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

        $articleForAuth = $this->dataHelper->get(
            'Article',
            ['Id' => $order->IdArticle],
            'Id, Title, Content, CreatedBy, PublishedBy, IdGroup, OnlyForMembers, LastUpdate'
        );

        if ($articleForAuth === false) {
            $this->raiseBadRequest("Article {$order->IdArticle} doesn't exist", __FILE__, __LINE__);
            return;
        }

        /** @var object{
         *     Id: int|string,
         *     Title: string,
         *     Content: string,
         *     CreatedBy: int|string,
         *     PublishedBy?: int|string|null,
         *     IdGroup?: int|string|null,
         *     OnlyForMembers?: bool|int|null,
         *     LastUpdate: string
         * } $articleForAuth
         */
        $articleAuthorization = ArticleAuthorizationRow::fromStdClass(ArticleRow::fromStdClass($articleForAuth));

        if (
            $this->authorizationDataHelper->canPersonReadOrderResults(
                $articleAuthorization,
                $connectedUser
            )
        ) {
            $repliesData = $this->dataHelper->gets('OrderReply', ['IdOrder' => $order->Id]);

            $replies = array_map(function ($row) {
                /** @var object{
                 *     Id: int|string,
                 *     IdPerson: int|string,
                 *     IdOrder: int|string,
                 *     Answers: string,
                 *     LastUpdate: string
                 * } $row
                 */
                return OrderReplyRow::fromStdClass($row);
            }, $repliesData);

            $participants = [];
            /** @var array<string, float|int> $results */
            $results = [];

            /** @var array<string> $options */
            $options = json_decode($order->Options) ?? [];
            foreach ($options as $option) {
                $results[$option] = 0;
            }

            foreach ($replies as $reply) {
                /** @var array<string, float|int> $answers */
                $answers = json_decode($reply->Answers) ?? [];

                $personData = $this->dataHelper->get(
                    'Person',
                    ['Id' => $reply->IdPerson],
                    'FirstName, LastName'
                );

                /** @var object{FirstName: string, LastName: string}|false $personData */
                $person = $personData ? PersonNameRow::fromStdClass($personData) : null;

                $participants[] = [
                    'name' => $person === null
                        ? '???'
                        : $person->FirstName . ' ' . $person->LastName,
                    'answers' => $answers,
                ];

                foreach ($answers as $article => $quantity) {
                    if (isset($results[$article])) {
                        $results[$article] += $quantity;
                    }
                }
            }

            $this->render('Article/views/order_results.latte', $this->getAllParams([
                'order'          => $order,
                'options'        => $options,
                'results'        => $results,
                'participants'   => $participants,
                'articleId'      => $articleId,
                'currentVersion' => Application::VERSION,
                'page'           => $connectedUser->getPage(),
                'btn_HistoryBack' => true,
                'btn_Parent'      => "/article/{$articleId}",
            ]));
        } else {
            $this->raiseForbidden(__FILE__, __LINE__);
        }
    }
}
