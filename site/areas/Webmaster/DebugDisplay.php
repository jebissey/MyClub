<?php

require_once __DIR__ . '/../../lib/PageDataDisplay.php';
require_once __DIR__ . '/../../lib/Database/Tables/Debug.php';

class DebugDisplay extends PagedDataDisplay {
    private $debug;
    private $message = '';

    public function __construct($page = 1) {
        $this->debug = new Debug();
        $this->handleClearDebug();
        parent::__construct($page);
    }
    private function handleClearDebug() {
        if (isset($_POST['clear_log'])) {
            $this->debug->del();
            $this->message = 'Debug has been cleared.';
        }
    }

    protected function loadData() {
        $result = $this->debug->get($this->getSkipCount(), self::RECORDS_PER_PAGE, $this->filters);
        $this->data = $result['data'];
        $this->totalPages = $this->calculateTotalPages($result['total']);
    }

    protected function renderContent() {
        $alertSuccess = $this->message ? '<div class="alert alert-success">' . htmlspecialchars($this->message) . '</div>' : '';

        $clearButton = '<form method="post" class="ms-3">
            <button type="submit" name="clear_log" class="btn btn-danger" 
                    onclick="return confirm(\'Are you sure you want to clear the debug log?\');">
                Clear Log
            </button>
        </form></div>';

        $headers = ['Date Time', 'Message'];
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
        return $alertSuccess . $table . ($this->data ? $clearButton:'');
    }
}
?>