<?php

declare(strict_types=1);

namespace app\modules\Article;

use app\enums\OrderVisibility;
use app\helpers\Application;
use app\helpers\ConnectedUser;
use app\models\ArticleDataHelper;
use app\models\OrderDataHelper;
use app\modules\Article\services\ArticleAuthorizationService;
use app\modules\Article\valueObjects\ArticleRow;
use app\modules\Article\valueObjects\OrderReplyRow;
use app\modules\Common\valueObjects\ClosingVisibilityRow;

/**
 * @phpstan-import-type OrderReplyRowShape from OrderReplyRow
 */
final class OrderController extends ClosableFormController
{
    public function __construct(
        Application $application,
        ArticleDataHelper $articleDataHelper,
        ArticleAuthorizationService $articleAuthorizationService,
        private readonly OrderDataHelper $orderDataHelper,
    ) {
        parent::__construct($application, $articleDataHelper, $articleAuthorizationService);
    }

    protected function formName(): string
    {
        return 'order';
    }

    protected function visibilityEnum(): string
    {
        return OrderVisibility::class;
    }

    protected function defaultVisibility(): string
    {
        return OrderVisibility::Redactor->value;
    }

    protected function canReadResults(ClosingVisibilityRow $form, ArticleRow $article, ConnectedUser $user): bool
    {
        return $this->authorizationService->canReadOrderResults($form, $article, $user);
    }

    protected function buildResultsParams(int $articleId): ?array
    {
        $order = $this->orderDataHelper->getWithCreator($articleId);
        if (!$order) {
            return null;
        }

        /** @var array<int, OrderReplyRowShape> $repliesData */
        $repliesData = $this->dataHelper->gets('OrderReply', ['IdOrder' => $order->id], '*');

        $replies = array_map(
            static fn(object $row): OrderReplyRow => OrderReplyRow::fromStdClass($row),
            $repliesData
        );

        $participants = [];
        /** @var array<string, float|int> $results */
        $results = [];

        /** @var array<string> $options */
        $options = json_decode($order->options) ?? [];
        foreach ($options as $option) {
            $results[$option] = 0;
        }

        foreach ($replies as $reply) {
            /** @var array<string, float|int> $answers */
            $answers = json_decode($reply->answers) ?? [];

            $participants[] = [
                'name'    => $this->participantName($reply->idPerson),
                'answers' => $answers,
            ];

            foreach ($answers as $option => $quantity) {
                if (isset($results[$option])) {
                    $results[$option] += $quantity;
                }
            }
        }

        return [
            'order'        => $order,
            'options'      => $options,
            'results'      => $results,
            'participants' => $participants,
        ];
    }
}
