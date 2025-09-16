<?php

namespace app\helpers;

use app\enums\ApplicationError;
use app\models\AuthorizationDataHelper;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogDataHelper;
use app\models\PageDataHelper;
use app\modules\Common\EmptyController;

class ErrorManager
{
    private DataHelper $dataHelper;
    private AuthorizationDataHelper $authorizationDataHelper;
    private LanguagesDataHelper $languagesDataHelper;
    private LogDataHelper $logDataHelper;
    private PageDataHelper $pageDataHelper;
    private EmptyController $emptyController;

    public function __construct(private Application $application)
    {
        $this->authorizationDataHelper = new AuthorizationDataHelper($application);
        $this->dataHelper = new DataHelper($application);
        $this->logDataHelper = new LogDataHelper($application);
        $this->languagesDataHelper = new LanguagesDataHelper($application);
        $this->pageDataHelper = new PageDataHelper($application, $this->authorizationDataHelper);
        $this->emptyController = new EmptyController(
            $application,
            $this->dataHelper,
            $this->languagesDataHelper,
            $this->pageDataHelper,
            $this->authorizationDataHelper,
            $this->logDataHelper
        );
    }

    public function raise(ApplicationError $code, string $message, int $timeout = 1000, bool $displayCode = true, $isWebmaster = false): void
    {
        $this->logDataHelper->add($code->value, $message);
        if ($this->isJsonExpected()) {
            http_response_code($code->value);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => $code->value,
                'message' => $message
            ]);
            return;
        }
        // for test with curl
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (stripos($ua, 'TestDevice') !== false) {
            $this->application->getFlight()->response()->status($code->value);
            $this->application->getFlight()->response()->write($message);
            return;
        }
        $translation = $this->languagesDataHelper->translate('Error' . $code->value);
        $setting = $this->dataHelper->get('Settings', ['Name' => 'Error_' . $code->value], 'Value');
        $result = '';
        if ($setting !== false && $setting->Value != '') $result =  $setting->Value;
        elseif ($translation != "-- Error{$code->value} --") $result = $translation;
        if ($result == '') {
            if ($displayCode) $result .= "<h1>{$code->value}</h1>";
            $result .= "<h2>$message</h2>";
        } else $timeout = max($code === ApplicationError::Error ? 30000 : 5000, $timeout);
        if ($code == ApplicationError::ServiceUnavailable && $isWebmaster) {
            $result = str_replace(
                '<a href="/" class="btn btn-primary mt-3">Retourner à l’accueil maintenant</a>',
                '<a href="/" class="btn btn-primary mt-3">Retourner à l’accueil maintenant</a>
    <p><a href="/maintenance/unset" class="btn btn-danger mt-3">Cancel maintenance</a></p>',
                $result
            );
        }
        $this->application->getFlight()->response()->status($code->value);
        $this->emptyController->render('Common/views/info.latte', [
            'content' => $result,
            'hasAuthorization' => $this->application->getConnectedUser()->get()->hasAutorization() ?? false,
            'currentVersion' => Application::VERSION,
            'timer' => $timeout,
            'previousPage' => false
        ]);
        if ($code != ApplicationError::Ok) exit;
        Application::unreachable('Ok isn\'t an error', __FILE__, __LINE__);
    }

    private function isJsonExpected(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
