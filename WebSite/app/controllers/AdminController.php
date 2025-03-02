<?php

namespace app\controllers;

class AdminController extends BaseController
{
    public function help()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {

            echo $this->latte->render('app/views/info.latte', $this->params->getAll([
                'content' => $this->settings->get('Help_admin'),
                'hasAuthorization' => $this->authorizations->isEventManager()
            ]));
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }

    public function home()
    {
        if ($this->getPerson(['EventManager', 'PersonManager', 'Redactor', 'Webmaster'])) {
            if ($this->authorizations->hasOnlyOneAutorization()) {
                if ($this->authorizations->isEventManager()) {
                    $this->flight->redirect('/eventManager');
                } else if ($this->authorizations->isPersonManager()) {
                    $this->flight->redirect('/personManager');
                } else if ($this->authorizations->isRedactor()) {
                    $this->flight->redirect('/articles');
                } else if ($this->authorizations->isWebmaster()) {
                    $this->flight->redirect('/webmaster');
                }
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                echo $this->latte->render('app/views/admin/admin.latte', $this->params->getAll([]));
            } else {
                $this->application->error470($_SERVER['REQUEST_METHOD'], __FILE__, __LINE__);
            }
        } else {
            $this->application->error403(__FILE__, __LINE__);
        }
    }
}
