<?php
namespace app\controllers;

use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

abstract class BaseController {
    protected PDO $pdo;
    protected Engine $flight;
    protected $latte;

    public function __construct(PDO $pdo, Engine $flight) {
        $this->pdo = $pdo;
        $this->flight = $flight;

        $this->latte = new LatteEngine();
        $this->latte->setTempDirectory(__DIR__ . '/../../var/latte/temp');
    }


    protected function sanitizeInput($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}