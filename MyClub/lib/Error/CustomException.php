<?php

class CustomException extends Exception {
    private $context;
    
    public function __construct($message, $context = [], $code = 0, Exception $previous = null) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }
    
    public function getContext() {
        return $this->context;
    }
}

?>