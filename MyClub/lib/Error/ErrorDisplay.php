<?php

require_once __DIR__ . '/../PageDataDisplay.php';
require_once __DIR__ . '/../../includes/Globals.php';

class ErrorDisplay extends PagedDataDisplay {
    private $filename;
    private $message = '';

    public function __construct($page = 1) {
        $this->filename = ERROR_FILE;
        $this->handleClearLog();
        parent::__construct($page);
    }
    private function handleClearLog() {
        if (isset($_POST['clear_log']) && file_exists($this->filename)) {
            file_put_contents($this->filename, '');
            $this->message = 'Error log has been cleared.';
        }
    }

    protected function loadData() {
        if (!file_exists($this->filename)) {
            return;
        }

        $content = file_get_contents($this->filename);
        $pattern = '/\[(.*?)\] ErrorException: (.*?) in (.*?) on line (\d+)\nStack trace:\n((?:(?!^\[).*\n)*)/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $allErrors = array_map(function($match) {
            return [
                'datetime' => $match[1],
                'message' => $match[2],
                'file' => str_replace('/var/www/sdb/b/2/cihy21/', '', $match[3]),
                'line' => $match[4],
                'stack_trace' => $match[5]
            ];
        }, $matches);

        $this->totalPages = $this->calculateTotalPages(count($allErrors));
        $this->data = array_slice($allErrors, $this->getSkipCount(), self::RECORDS_PER_PAGE);
    }

    protected function renderContent() {
        $alertSuccess = $this->message ? '<div class="alert alert-success">' . htmlspecialchars($this->message) . '</div>' : '';

        $clearButton = '<form method="post" class="ms-3">
            <button type="submit" name="clear_log" class="btn btn-danger" 
                    onclick="return confirm(\'Are you sure you want to clear the error log?\');">
                Clear Log
            </button>
        </form></div>';

        $headers = ['Date Time', 'File', 'Line', 'Stack'];
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