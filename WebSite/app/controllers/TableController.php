<?php

namespace app\controllers;

abstract class TableController extends BaseController
{
    protected $itemsPerPage = 10;

    protected function prepareTableData($query, $filters = [], $page = 1)
    {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query = $query->where("$key LIKE '%$value%'");
            }
        }

        //var_dump($filters);
        //var_dump($query->getQuery());
        //die();

        $totalItems = $this->pdo->query("SELECT COUNT(*) FROM (" . $query->getQuery() . ")")->fetchColumn();
        $totalPages = ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $query = $query->limit($this->itemsPerPage)->offset(($currentPage - 1) * $this->itemsPerPage);

        return [
            'items' => $query->fetchAll(),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'filters' => $filters
        ];
    }

    protected function buildPaginationParams($filters)
    {
        $params = [];
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $params[$key] = urlencode($value);
            }
        }
        return $params;
    }
}
