<?php

abstract class PagedDataDisplay {
    protected const RECORDS_PER_PAGE = 10;
    protected $currentPage = 1;
    protected $totalPages = 1;
    protected $data = [];
    protected $filters = [];
    protected $filterFields = [];

    public function __construct($page = 1) {
        $this->currentPage = max(1, intval($page));
        $this->loadData();
    }

    abstract protected function loadData();

    protected function getSkipCount() {
        return ($this->currentPage - 1) * static::RECORDS_PER_PAGE;
    }

    protected function calculateTotalPages($totalRecords) {
        return max(1, ceil($totalRecords / static::RECORDS_PER_PAGE));
    }

    protected function processSubmittedFilters($filterFields) {
        $filters = [];
        foreach ($filterFields as $name => $placeholder) {
            if (isset($_GET[$name]) && $_GET[$name] !== '') {
                $filters[ucfirst($name)] = $_GET[$name];
            }
        }
        return $filters;
    }

    protected function generateFilterForm($filterFields, $additionalGets = []) {
        if (empty($filterFields)) {
            return '';
        }

        $html = '<form method="GET" class="row g-3 mb-4">';
        
        // Add hidden fields for additional GET parameters
        foreach ($additionalGets as $name => $value) {
            $html .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                htmlspecialchars($name),
                htmlspecialchars($value)
            );
        }

        // Add filter fields
        foreach ($filterFields as $name => $placeholder) {
            $value = isset($_GET[$name]) ? htmlspecialchars($_GET[$name]) : '';
            $html .= sprintf(
                '<div class="col-md-3">
                    <input type="text" class="form-control" name="%s" placeholder="%s" value="%s">
                </div>',
                $name, $placeholder, $value
            );
        }

        $html .= '<div class="col-12">
            <button type="submit" class="btn btn-primary">Filter</button>
            </div></form>';
        
        return $html;
    }

    protected function generatePaginationControls($additionalGets = []) {
        $currentUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Preserve all current GET parameters except 'page'
        $queryParams = $_GET;
        unset($queryParams['page']);
        
        $buildUrl = function($page) use ($queryParams, $currentUrl, $additionalGets) {
            $params = array_merge($additionalGets, $queryParams, ['page' => $page]);
            return $currentUrl . '?' . http_build_query($params);
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

    public function render($filterFields = [], $additionalGets = []) {
        $this->filterFields = $filterFields;
        $this->filters = $this->processSubmittedFilters($filterFields);
        
        // Reload data with new filters
        $this->loadData();
        
        $content = '';
        
        // Generate filter form if filter fields are provided
        if (!empty($filterFields)) {
            $content .= $this->generateFilterForm($filterFields, $additionalGets);
        }
        
        // Generate content
        $content .= $this->renderContent();
        
        // Add pagination controls
        $content .= $this->generatePaginationControls($additionalGets);
        
        return $content;
    }

    abstract protected function renderContent();
}
