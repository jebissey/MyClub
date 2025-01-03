<?php

require_once __DIR__ . '/../PageDataDisplay.php';
require_once __DIR__ . '/../../includes/Globals.php';

class ErrorLogViewer extends PagedDataDisplay {
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

    public function render() {
        $content = $this->message ? '<div class="alert alert-success">' . htmlspecialchars($this->message) . '</div>' : '';
        
        $content .= '<div class="d-flex justify-content-between align-items-center mb-4">';
        $content .= $this->generatePaginationControls('');
        $content .= '<form method="post" class="ms-3">
            <button type="submit" name="clear_log" class="btn btn-danger" 
                    onclick="return confirm(\'Are you sure you want to clear the error log?\');">
                Clear Log
            </button>
        </form></div>';
        
        $content .= $this->generateErrorTable();
        
        return $content;
    }

    private function generateErrorTable() {
        $style = '<style>
            .tooltip-stack { position: relative; display: inline-block; cursor: pointer; }
            .tooltip-stack .tooltip-content {
                visibility: hidden;
                width: 500px;
                background-color: rgba(0,0,0,0.9);
                color: #fff;
                text-align: left;
                padding: 5px;
                position: absolute;
                z-index: 1;
                bottom: 125%;
                left: 50%;
                margin-left: -250px;
                white-space: pre;
                border-radius: 6px;
            }
            .tooltip-stack:hover .tooltip-content { visibility: visible; }
        </style>';
        
        $table = $style . '<div class="table-responsive"><table class="table table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Date Time</th>
                    <th>File</th>
                    <th>Line</th>
                    <th>Stack Trace</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($this->data as $error) {
            $table .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>
                        <div class="tooltip-stack">
                            <span class="badge bg-info">View Trace</span>
                            <pre class="tooltip-content">%s</pre>
                        </div>
                    </td>
                </tr>',
                htmlspecialchars($error['datetime']),
                htmlspecialchars($error['file']),
                htmlspecialchars($error['line']),
                htmlspecialchars($error['stack_trace'])
            );
        }
        
        return $table . '</tbody></table></div>';
    }
}
?>