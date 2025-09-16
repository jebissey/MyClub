<?php

namespace app\modules\Common;

use \Envms\FluentPDO\Queries\Select;

use app\helpers\Application;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\GenericDataHelper;
use app\models\LanguagesDataHelper;
use app\models\PageDataHelper;

abstract class TableController extends AbstractController
{
    protected int $itemsPerPage = 10;

    public function __construct(
        Application $application,
        private GenericDataHelper $genericDataHelper,
        DataHelper $dataHelper,
        LanguagesDataHelper $languagesDataHelper,
        PageDataHelper $pageDataHelper,
        AuthorizationDataHelper $authorizationDataHelper
    ) {
        parent::__construct($application, $dataHelper, $languagesDataHelper, $pageDataHelper, $authorizationDataHelper);
    }

    protected function prepareTableData(Select $query, array $filters = [], int $page = 1): array
    {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query = $query->where("$key LIKE ?");
                $values[] = "%$value%";
            }
        }

        $totalItems = $this->genericDataHelper->countOf($query->getQuery());
        $totalPages = ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $query = $query->limit($this->itemsPerPage)->offset(($currentPage - 1) * $this->itemsPerPage);

        //var_dump($filters);
        //var_dump($query->getQuery());
        //var_dump($values ?? null);
        //die();

        $stmt = $this->application->getPdo()->prepare($query->getQuery());
        $stmt->execute($values ?? null);
        $items = $stmt->fetchAll();

        return [
            'items' => $items,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'filters' => $filters
        ];
    }

    protected function buildPaginationParams($filters): array
    {
        $params = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) $params[$key] = urlencode($value);
        }
        return $params;
    }
}
