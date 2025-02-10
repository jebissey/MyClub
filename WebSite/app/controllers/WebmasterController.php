<?php

namespace app\controllers;

use DateTime;
use flight\Engine;
use PDO;
use app\helpers\Client;
use app\helpers\Params;
use app\helpers\PasswordManager;
use app\helpers\Settings;

class WebmasterController extends BaseController
{
    private PDO $pdoForLog;
    private Settings $settings;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->pdoForLog = \app\helpers\database\Database::getInstance()->getPdoForLog();
        $this->settings = new Settings($this->pdo);
    }

    public function help() 
    {
        $this->getPerson();

        echo $this->latte->render('app/views/info.latte', [
            'content' => $this->settings->getHelpWebmaster(),
            'hasAuthorization' => $this->authorizations->hasAutorization()
        ]);
    }

    public function home()
    {
        $this->getPerson();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/admin/webmaster.latte', $this->params->getAll([]));
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }
}