<?php

declare(strict_types=1);

namespace app\modules\Common;

use Envms\FluentPDO\Queries\Select;
use app\helpers\Application;

abstract class TableController extends AbstractController
{
    private int $itemsPerPage = 10;

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @param array<string,string|null> $filters
     * @return array{
     *     items: array<int, object>,
     *     currentPage: int,
     *     totalPages: int,
     *     filters: array<string,string|null>
     * }
     */
    protected function prepareTableData(Select $query, array $filters = [], bool $usePdoForLog = false): array
    {
        $tablePage = $this->flight->request()->query['tablePage'] ?? null;
        $page = is_numeric($tablePage) ? (int) $tablePage : 1;

        /** @var array<int,string> $values */
        $values = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query = $query->where("$key LIKE ?");
            $values[] = "%$value%";
        }

        $totalItems = $this->dataHelper->count($query->getQuery(), $values, $usePdoForLog);
        $totalPages = (int) ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));

        $query = $query
            ->limit($this->itemsPerPage)
            ->offset(($currentPage - 1) * $this->itemsPerPage);

        /** @var array<int, object> $items */
        $items = $this->dataHelper->query($query->getQuery(), $values, $usePdoForLog) ?: [];

        return [
            'items' => $items,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'filters' => $filters,
        ];
    }

    /**
     * @param array<string,string> $filters
     * @return array<string,string>
     */
    protected function buildPaginationParams(array $filters): array
    {
        $params = [];

        foreach ($filters as $key => $value) {
            if ($value !== '') {
                $params[$key] = urlencode($value);
            }
        }

        return $params;
    }
}
