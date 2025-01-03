<?php

require_once __DIR__ . '/../PageDataDisplay.php';
require_once __DIR__ . '/../Database/Tables/Log.php';

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

    private function generateFilterForm() {
        $fields = [
            'os' => 'Filter OS',
            'browser' => 'Filter Browser',
            'type' => 'Filter Type',
            'email' => 'Filter Email'
        ];

        $html = '<form method="GET" class="row g-3 mb-4">';
        foreach ($fields as $name => $placeholder) {
            $value = isset($this->filters[ucfirst($name)]) ? htmlspecialchars($this->filters[ucfirst($name)]) : '';
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

    public function render($otherGets) {
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

        return $this->generateFilterForm() . $table . $this->generatePaginationControls($otherGets);
    }
}
