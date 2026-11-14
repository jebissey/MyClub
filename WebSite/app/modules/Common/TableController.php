<?php

declare(strict_types=1);

namespace app\modules\Common;

use Envms\FluentPDO\Queries\Select;
use stdClass;
use app\modules\Common\valueObjects\TableRow;

abstract class TableController extends AbstractController
{
    private int $itemsPerPage = 10;

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
     * @param array<string,string|null> $filters
     * @return array{
     *     items: list<TableRow>,
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

        /** @var array<int, stdClass> $rows */
        $rows = $this->dataHelper->query($query->getQuery(), $values, $usePdoForLog) ?: [];

        return [
            'items' => array_map(
                static fn(object $row): TableRow => TableRow::fromStdClass($row),
                array_values($rows)
            ),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'filters' => $filters,
        ];
    }


    /**
     * In-memory equivalent of prepareTableData(), for data that does not come from a SQL query.
     * Filters use a case-insensitive "contains" match, like the LIKE filters of the SQL version.
     *
     * @param list<array<string, string>> $rows
     * @param array<string, string|null> $filters
     * @return array{
     *     items: list<TableRow>,
     *     currentPage: int,
     *     totalPages: int,
     *     filters: array<string, string|null>
     * }
     */
    protected function prepareTableDataFromArray(array $rows, array $filters = []): array
    {
        $tablePage = $this->flight->request()->query['tablePage'] ?? null;
        $page = is_numeric($tablePage) ? (int) $tablePage : 1;

        $rows = array_values(array_filter(
            $rows,
            static function (array $row) use ($filters): bool {
                foreach ($filters as $key => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (mb_stripos($row[$key] ?? '', $value) === false) {
                        return false;
                    }
                }
                return true;
            }
        ));

        $totalPages = (int) ceil(count($rows) / $this->itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $this->itemsPerPage;

        $items = [];
        foreach (array_slice($rows, $offset, $this->itemsPerPage) as $index => $row) {
            $items[] = new TableRow((string) ($offset + $index), $row);
        }

        return [
            'items' => $items,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'filters' => $filters,
        ];
    }
}
