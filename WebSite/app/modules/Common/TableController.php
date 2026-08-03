<?php

declare(strict_types=1);

namespace app\modules\Common;

use Envms\FluentPDO\Queries\Select;
use PDO;
use app\helpers\Application;

abstract class TableController extends AbstractController
{
    private int $itemsPerPage = 10;

    public function __construct(Application $application)
    {
        parent::__construct($application);
    }

    /**
     * @param array<string,string> $filters
     * @return array{
     *     items: array<int, object>,
     *     currentPage: int,
     *     totalPages: int,
     *     filters: array<string,string>
     * }
     */
    protected function prepareTableData(Select $query, array $filters = [], bool $usePdoForLog = false): array
    {
        $pdo = $usePdoForLog
            ? $this->application->getPdoForLog()
            : $this->application->getPdo();

        $page = (int)($this->flight->request()->query['tablePage'] ?? 1);

        /** @var array<int,string> $values */
        $values = [];

        foreach ($filters as $key => $value) {
            if ($value !== '') {
                $query = $query->where("$key LIKE ?");
                $values[] = "%$value%";
            }
        }

        $totalItems = $this->count($query->getQuery(), $pdo, $values);
        $totalPages = (int)ceil($totalItems / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));

        $query = $query
            ->limit($this->itemsPerPage)
            ->offset(($currentPage - 1) * $this->itemsPerPage);

        $stmt = $pdo->prepare($query->getQuery());
        $stmt->execute($values);

        /** @var array<int, object> $items */
        $items = $stmt->fetchAll(PDO::FETCH_OBJ);

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

    /**
     * @param array<int,string> $values
     */
    private function count(string $sql, PDO $pdo, array $values = []): int
    {
        $countSql = "SELECT COUNT(*) FROM ({$sql}) AS sub";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($values);

        return (int)$stmt->fetchColumn();
    }
}
