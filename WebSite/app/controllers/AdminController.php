<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Client;
use app\helpers\Params;
use app\helpers\PasswordManager;

class AdminController extends BaseController
{
    private PDO $pdoForLog;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
    }

    public function root()
    {
        $this->getPerson();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/user/user.latte', $this->params->getAll([]));
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }
}