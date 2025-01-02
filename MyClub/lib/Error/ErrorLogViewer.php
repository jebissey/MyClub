<?php
require_once __DIR__ . '/../../includes/Globals.php';

class ErrorLogViewer {
    private $filename = ERROR_FILE;
    private $errorsPerPage = 10;
    private $currentPage = 1;
    private $totalErrors = 0;
    private $errors = [];
    private $message = '';
    
    public function __construct() {
        if (isset($_POST['clear_log'])) {
            $this->clearErrorLog();
            $this->message = 'Error log has been cleared.';
        }

        if (isset($_GET['page'])) {
            $this->currentPage = max(1, intval($_GET['page']));
        }
        $this->parseErrorLog();
    }
    
    private function parseErrorLog() {
        if (!file_exists($this->filename)) {
            return;
        }
        
        $content = file_get_contents($this->filename);
        $pattern = '/\[(.*?)\] ErrorException: (.*?) in (.*?) on line (\d+)\nStack trace:\n((?:(?!^\[).*\n)*)/m';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $this->errors[] = [
                'datetime' => $match[1],
                'message' => $match[2],
                'file' => str_replace('/var/www/sdb/b/2/cihy21/', '', $match[3]),
                'line' => $match[4],
                'stack_trace' => $match[5]
            ];
        }
        $this->totalErrors = count($this->errors);
    }
    
    public function clearErrorLog() {
        if (file_exists($this->filename)) {
            file_put_contents($this->filename, '');
            $this->errors = [];
            $this->totalErrors = 0;
        }
    }
    
    public function getCurrentPageErrors() {
        $start = ($this->currentPage - 1) * $this->errorsPerPage;
        return array_slice($this->errors, $start, $this->errorsPerPage);
    }
    
    public function getTotalPages() {
        return max(1, ceil($this->totalErrors / $this->errorsPerPage));
    }
    
    public function render() {
        $totalPages = $this->getTotalPages();
        $errors = $this->getCurrentPageErrors();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Error Log Viewer</title>
        <style>
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
            th { background-color: #f5f5f5; }
            .tooltip { position: relative; display: inline-block; cursor: pointer; }
            .tooltip .tooltiptext {
                visibility: hidden;
                width: 500px;
                background-color: black;
                color: #fff;
                text-align: left;
                padding: 5px;
                position: absolute;
                z-index: 1;
                bottom: 125%;
                left: 50%;
                margin-left: -250px;
                white-space: pre;
            }
            .tooltip:hover .tooltiptext { visibility: visible; }
            .navigation { margin: 20px 0; }
            .navigation a, .navigation button {
                padding: 5px 10px;
                margin: 0 5px;
                text-decoration: none;
                border: 1px solid #ddd;
                background-color: #f5f5f5;
                color: #333;
            }
            .message { 
                padding: 10px; 
                margin: 10px 0; 
                background-color: #dff0d8; 
                border: 1px solid #d6e9c6; 
                color: #3c763d; 
            }
        </style>
    </head>
    <body>
        <?php if ($this->message): ?>
            <div class="message"><?php echo htmlspecialchars($this->message); ?></div>
        <?php endif; ?>

        <div class="navigation">
            <a href="?page=1">First</a>
            <a href="?page=<?php echo max(1, $this->currentPage - 1); ?>">Previous</a>
            <span>Page <?php echo $this->currentPage; ?> of <?php echo $totalPages; ?></span>
            <a href="?page=<?php echo min($totalPages, $this->currentPage + 1); ?>">Next</a>
            <form method="post" style="display: inline;">
                <button type="submit" name="clear_log" onclick="return confirm('Are you sure you want to clear the error log?');">
                    Clear Log
                </button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Date Time</th>
                    <th>File</th>
                    <th>Line</th>
                    <th>Stack Trace</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($errors as $error): ?>
                <tr>
                    <td><?php echo htmlspecialchars($error['datetime']); ?></td>
                    <td><?php echo htmlspecialchars($error['file']); ?></td>
                    <td><?php echo htmlspecialchars($error['line']); ?></td>
                    <td>
                        <div class="tooltip">
                            📖
                            <pre class="tooltiptext"><?php echo htmlspecialchars($error['stack_trace']); ?></pre>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
</html>

<?php
    }
}

?>