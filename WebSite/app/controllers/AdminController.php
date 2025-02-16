<?php

namespace app\controllers;

class AdminController extends BaseController
{
    public function help()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            echo $this->latte->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settings->getHelpAdmin(),
                'hasAuthorization' => $this->authorizations->isEventManager()
            ]));
        }
    }

    public function home()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/admin/admin.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        }
    }
}
