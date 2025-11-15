<?php

declare(strict_types=1);

namespace app\modules\Common;

use \Envms\FluentPDO\Queries\Select;
use PDO;

use app\helpers\Application;

abstract class TableController extends AbstractController
{
    private int $itemsPerPage = 10;

    public function __construct(
        Application $application,
    ) {
        parent::__construct($application);
    }

    protected function prepareTableData(Select $query, array $filters = [], bool $usePdoForLog = false): array
    {
        $pdo = null;
        if ($usePdoForLog) $pdo = $this->application->getPdoForLog();
        else               $pdo = $this->application->getPdo();
        
        $page = (int)($this->flight->request()->query['tablePage'] ?? 1);
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $query = $query->where("$key LIKE ?");
                $values[] = "%$value%";
            }
        }

        $totalItems = $this->count($query->getQuery(), $pdo);
        $totalPages = ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $query = $query->limit($this->itemsPerPage)->offset(($currentPage - 1) * $this->itemsPerPage);

        //var_dump($filters);
        //var_dump($query->getQuery());
        //var_dump($values ?? null);
        //die();

        $stmt = $pdo->prepare($query->getQuery());
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

    #region private functions  
    private function count(string $query, PDO $pdo): int
    {
        return $pdo->query("SELECT COUNT(*) FROM (" . $query . ")")->fetchColumn();
    }
}
