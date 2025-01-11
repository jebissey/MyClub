<?php

require_once __DIR__ . '/../../../lib/PageDataDisplay.php';
require_once __DIR__ . '/../../../lib/Database/Tables/Log.php';

class LogDisplay extends PagedDataDisplay {
    private $log;

    public function __construct($page = 1, $filters = []) {
        $this->log = new Log();
        parent::__construct($page, $filters);
    }

    protected function loadData() {
        $result = $this->log->get($this->getSkipCount(), self::RECORDS_PER_PAGE, $this->filters);
        $this->data = $result['data'];
        $this->totalPages = $this->calculateTotalPages($result['total']);
    }

    protected function renderContent() {
        $headers = ['OS', 'Browser', 'Screen', 'Client Type', 'URI', 'Email', 'Timestamp'];
        $table = '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr>';
        foreach ($headers as $header) {
            $table .= '<th>' . $header . '</th>';
        }
        
        $table .= '</tr></thead><tbody>';

        foreach ($this->data as $row) {
            $table .= '<tr>';
            foreach ($row as $value) {
                $table .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $table .= '</tr>';
        }
        
        $table .= '</tbody></table></div>';
        
        return $table;
    }
}
