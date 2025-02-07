<?php

namespace app\helpers;

class Params {
    private array $commonParams;

    public function __construct(array $params) {
        $this->commonParams = $params;
    }

    public function getAll(array $specificParams): array {
        return array_merge($specificParams, $this->commonParams);
    }
}