<?php

declare(strict_types=1);

namespace app\modules\User\services;

use app\helpers\ConnectedUser;
use app\helpers\News;
use app\helpers\To;
use app\models\DesignDataHelper;
use app\models\SurveyDataHelper;
use app\modules\User\valueObjects\PendingItems;

/**
 * @phpstan-import-type PendingDesign from PendingItems
 */
final class PendingItemsProvider
{
    public function __construct(
        private readonly SurveyDataHelper $surveyDataHelper,
        private readonly DesignDataHelper $designDataHelper,
        private readonly News $news
    ) {
    }

    /**
     * Collects everything the connected user still has to answer or read.
     */
    public function forUser(string $userEmail, ConnectedUser $connectedUser): PendingItems
    {
        $surveys = array_values(array_filter(
            $this->surveyDataHelper->getPendingSurveyResponses(),
            static fn($item) => strcasecmp($item->email, $userEmail) === 0
        ));

        /** @var list<PendingDesign> $designs */
        $designs = array_values(array_filter(
            $this->designDataHelper->getPendingDesignResponses(),
            static fn($item) => strcasecmp(To::str($item->Email), $userEmail) === 0
        ));

        return new PendingItems(
            surveys: $surveys,
            designs: $designs,
            news: (bool) $this->news->anyNews($connectedUser)
        );
    }
}
