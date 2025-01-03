<?php

abstract class PagedDataDisplay {
    protected const RECORDS_PER_PAGE = 10;
    protected $currentPage = 1;
    protected $totalPages = 1;
    protected $data = [];
    protected $filters = [];

    public function __construct($page = 1, $filters = []) {
        $this->currentPage = max(1, intval($page));
        $this->filters = $filters;
        $this->loadData();
    }

    abstract protected function loadData();

    protected function getSkipCount() {
        return ($this->currentPage - 1) * static::RECORDS_PER_PAGE;
    }

    protected function calculateTotalPages($totalRecords) {
        return max(1, ceil($totalRecords / static::RECORDS_PER_PAGE));
    }

    protected function generatePaginationControls($otherGets) {
        $queryParams = $this->filters;
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $buildUrl = function($page) use ($queryParams, $currentUrl, $otherGets) {
            $params = array_merge($queryParams, ['page' => $page]);
            return $currentUrl . '?' . http_build_query($params) . $otherGets;
        };

        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        $html .= sprintf(
            '<li class="page-item %s"><a class="page-link" href="%s">&laquo;</a></li>',
            $this->currentPage == 1 ? 'disabled' : '',
            $buildUrl(1)
        );
        
        for ($i = max(1, $this->currentPage - 2); $i <= min($this->totalPages, $this->currentPage + 2); $i++) {
            $html .= sprintf(
                '<li class="page-item %s"><a class="page-link" href="%s">%d</a></li>',
                $i == $this->currentPage ? 'active' : '',
                $buildUrl($i),
                $i
            );
        }
        
        $html .= sprintf(
            '<li class="page-item %s"><a class="page-link" href="%s">&raquo;</a></li>',
            $this->currentPage == $this->totalPages ? 'disabled' : '',
            $buildUrl($this->totalPages)
        );
        
        $html .= '</ul></nav>';
        return $html;
    }
}

