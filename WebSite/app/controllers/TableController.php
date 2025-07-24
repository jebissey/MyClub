<?php

namespace app\controllers;

use app\helpers\Generic;

abstract class TableController extends BaseController
{
    protected int $itemsPerPage = 10;

    protected function prepareTableData($query, $filters = [], $page = 1): array
    {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query = $query->where("$key LIKE ?");
                $values[] = "%$value%";
            }
        }

        //var_dump($filters);
        //var_dump($query->getQuery());
        //die();

        $totalItems = (new Generic())->countOf($query->getQuery());
        $totalPages = ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $query = $query->limit($this->itemsPerPage)->offset(($currentPage - 1) * $this->itemsPerPage);

        return [
            'items' => isset($values) ? $query->fetchAll($values) : $query->fetchAll(),
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
