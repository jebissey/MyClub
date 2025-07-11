<?php

namespace app\helpers;

use app\controllers\BaseController;
use PDO;
use flight\Engine;
use Latte\Engine as LatteEngine;

class Application
{
    private PDO $pdo;
    private Engine $flight;
    private Settings $settings;
    private $latte;
    private $authorizations;
    private $version;

    public function __construct(PDO $pdo, Engine $flight)
    {
        $this->pdo = $pdo;
        $this->flight = $flight;
        $this->settings = new Settings($this->pdo);
        $this->latte = new LatteEngine();
        $this->authorizations = new Authorization($this->pdo);
        $this->version = BaseController::GetVersion();
    }


    public function help()
    {
        $content = $this->latte->renderToString('app/views/info.latte', [
            'content' => $this->settings->get('Help_home'),
            'hasAuthorization' => $this->authorizations->hasAutorization(),
            'currentVersion' => $this->version
        ]);
        echo $content;
    }

    public function legalNotice()
    {
        $content = $this->latte->renderToString('app/views/info.latte', [
            'content' => $this->settings->get('LegalNotices'),
            'hasAuthorization' => $this->authorizations->hasAutorization(),
            'currentVersion' => $this->version
        ]);
        echo $content;
    }


    public function message($message, $timeout = 5000, $code = 200)
    {
        $this->error($code, $message, $timeout, false);
    }

    public function error403($file, $line, $timeout = 1000)
    {
        $this->error(403, "Page not allowed in file $file at line $line", $timeout);
    }

    public function error404($timeout = 1000)
    {
        $this->error(404, 'Page not found', $timeout);
    }

    public function error470($requestMethod, $file, $line, $timeout = 1000)
    {
        $this->error(470, "Method $requestMethod invalid in file $file at line $line", $timeout);
    }

    public function error471($parameter, $file, $line, $timeout = 1000)
    {
        $this->error(471, "Parameter $parameter invalid in file $file at line $line", $timeout);
    }

    public function error472($parameterName, $file, $line, $timeout = 1000)
    {
        $this->error(472, "Missing Parameter $parameterName invalid in file $file at line $line", $timeout);
    }

    public function error479($email, $file, $line, $timeout = 1000)
    {
        $this->error(479, "Email address: $email inactivated in file $file at line $line", $timeout);
    }

    public function error480($email, $file, $line, $timeout = 1000)
    {
        $this->error(480, "Unknown user with this email address: $email in file $file at line $line", $timeout);
    }

    public function error481($email, $file, $line, $timeout = 1000)
    {
        $this->error(481, "Invalid email address: $email in file $file at line $line", $timeout);
    }

    public function error482($message, $file, $line, $timeout = 1000)
    {
        $this->error(482, "Invalid password: $message in file $file at line $line", $timeout);
    }


    public function error497($token, $file, $line, $timeout = 1000)
    {
        $this->error(497, "Token $token is expired in file $file at line $line", $timeout);
    }

    public function error498($table, $token, $file, $line, $timeout = 1000)
    {
        $this->error(498, "Record with token $token not found in table $table in file $file at line $line", $timeout);
    }

    public function error499($table, $id, $file, $line, $timeout = 1000)
    {
        $this->error(499, "Record $id not found in table $table in file $file at line $line", $timeout);
    }

    public function error500($message, $file, $line, $timeout = 5000)
    {
        $this->error(500, "Internal error: $message in file $file at line $line", $timeout);
    }

    #region private Function
    private function error($code, $message, $timeout = 1000, $displayCode = true)
    {
        $this->flight->setData('code', $code);
        $this->flight->setData('message', $message);

        if ($code && $displayCode) {
            echo "<h1>$code</h1>";
        }
        echo "<h2>$message</h2>";
        echo "<script>
            setTimeout(function() {
                window.location.href = '/';
            }, $timeout);
        </script>";
    }
}
