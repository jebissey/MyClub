<?php

namespace app\controllers;

use flight\Engine;
use PDO;
use app\helpers\Settings;

class AdminController extends BaseController
{
    private Settings $settings;

    public function __construct(PDO $pdo, Engine $flight)
    {
        parent::__construct($pdo, $flight);
        $this->settings = new Settings($this->pdo);
    }


    public function help() 
    {
        echo $this->latte->render('app/views/info.latte', [
            'content' => $this->settings->getHelpAdmin(),
            'hasAuthorization' => $this->authorizations->isEventManager()
        ]);
    }

    public function home()
    {
        $this->getPerson();

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo $this->latte->render('app/views/admin/admin.latte', $this->params->getAll([]));
        } else {
            $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
        }
    }
}