<?php

namespace app\helpers;

use app\enums\ApplicationError;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogDataHelper;
use app\modules\Common\EmptyController;

/*
TODO find the good way to manage error with flight and use the hook for logging page.
*/

class ErrorManager
{
    private Application $application;
    private EmptyController $emptyController;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->emptyController = new EmptyController($application);
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true): void
    {
        (new LogDataHelper($this->application))->add($code->value, $message);

        if ($this->isJsonExpected()) {
            http_response_code($code->value);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => $code->value,
                'message' => $message
            ]);
            return;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($ua, 'TestDevice') !== false) {
            $this->application->getFlight()->response()->status($code->value);
            $this->application->getFlight()->response()->write($message);
            return;
        }
        http_response_code($code->value);
        header('Content-Type: text/html; charset=utf-8');

        $translation = (new LanguagesDataHelper($this->application))->translate('Error' . $code->value);
        $setting = (new DataHelper($this->application))->get('Settings', ['Name' => 'Error_' . $code->value], 'Value');
        $result = '';
        if ($setting !== false && $setting->Value != '') $result =  $setting->Value;
        elseif ($translation != "-- Error{$code->value} --") $result = $translation;
        if ($result !== '') {
            echo $result;
            $timeout = 5000;
        } else {
            if ($displayCode) echo "<h1>{$code->value}</h1>";
            echo "<h2>$message</h2>";
        }
        $seconds = intval($timeout / 1000);
        echo "<meta http-equiv='refresh' content='{$seconds};url=/' />";

        /*$content = '';
            if ($displayCode) $content = "<h1>{$code->value}</h1>";
            $content .= "<h2>$message</h2>";

            $this->emptyController->render('Common/views/info.latte', [
                'content' => $content,
                'hasAuthorization' => $this->emptyController->connectedUser->get()->hasAutorization() ?? false,
                'currentVersion' => Application::VERSION,
                'timer' => $timeout,
                'previousPage' => false
            ]);
            $this->application->getFlight()->response()->status($code->value);*/
    }

    private function isJsonExpected(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
