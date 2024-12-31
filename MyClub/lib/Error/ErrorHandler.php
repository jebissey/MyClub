<?php

class ErrorHandler {
    private $developmentMode;
    private $logFile;
    
    public function __construct($developmentMode = false, $logFile = null) {
        $this->developmentMode = $developmentMode;
        
        if ($logFile === null) {
            $this->logFile = dirname(__DIR__) . '/../logs/errors.log';
        } else {
            $this->logFile = $logFile;
        }
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0664); 
        }
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleFatalError']);
    }
    
    public function handleError($level, $message, $file, $line) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
    
    public function handleException($exception) {
        $this->logError($exception);
        
        if ($this->developmentMode) {
            $this->displayDetailedError($exception);
        } else {
            $this->displayFriendlyError();
        }
    }
    
    public function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
    
    private function logError($exception) {
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        if ($exception instanceof CustomException) {
            $message .= "Context: " . print_r($exception->getContext(), true) . "\n";
        }
        $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n\n";
        file_put_contents($this->logFile, $message, FILE_APPEND);

    }
    
    private function displayDetailedError($exception) {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        
        echo "\n\n<div style='background-color: #fff; color: #333; padding: 20px; margin: 20px; border: 2px solid #f00; border-radius: 5px;'>";
        echo "<h1 style='color: #f00;'>Une erreur est survenue</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>Fichier:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Ligne:</strong> " . $exception->getLine() . "</p>";
        echo "<p><strong>Log file:</strong> " . htmlspecialchars($this->logFile) . "</p>";
        echo "<h2>Trace:</h2>";
        echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto;'>" . 
             htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        
        if ($exception instanceof CustomException) {
            echo "<h2>Contexte:</h2>";
            echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto;'>" . 
                 htmlspecialchars(print_r($exception->getContext(), true)) . "</pre>";
        }
        echo "</div>";
    }
    
    private function displayFriendlyError() {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo "\n\n<div style='background-color: #fff; color: #333; padding: 20px; margin: 20px; border: 2px solid #f00; border-radius: 5px;'>";
        echo "<h1>Désolé, une erreur est survenue</h1>";
        echo "<p>Nos équipes ont été notifiées et travaillent à résoudre le problème.</p>";
        echo "</div>";
    }
}
?>