<?php

declare(strict_types=1);

namespace app\helpers;

use app\enums\ApplicationError;
use app\models\DataHelper;
use app\models\LanguagesDataHelper;
use app\models\LogWriterDataHelper;
use app\modules\Common\EmptyController;
use app\modules\Common\viewModels\InfoViewModel;

//not final because of tests
readonly class ErrorManager
{
    private DataHelper $dataHelper;
    private LanguagesDataHelper $languagesDataHelper;
    private LogWriterDataHelper $logWriterDataHelper;
    private EmptyController $emptyController;

    public function __construct(private Application $application)
    {
        $this->logWriterDataHelper = new LogWriterDataHelper($application, $this);
        $this->dataHelper = new DataHelper($application->getPdo(), $this, $application->getPdoForLog());
        $this->languagesDataHelper = new LanguagesDataHelper($application, $this);
        $this->emptyController = new EmptyController($application);
    }

    public function raise(
        ApplicationError $code,
        string $message,
        int $timeout = 3000,
        bool $displayCode = true,
        bool $isWebmaster = false
    ): void {
        $this->logWriterDataHelper->add((string)$code->value, $message);
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
        $ua = is_string($ua) ? $ua : '';
        if (stripos($ua, 'TestDevice') !== false) {
            $this->application->getFlight()->response()->status($code->value);
            $this->application->getFlight()->response()->write($message);
            return;
        }
        $translation = $this->languagesDataHelper->translate('Error' . $code->value);
        $result = '';
        if ($translation != "-- Error{$code->value} --") {
            $result = $translation;
        }
        if ($result === '') {
            if ($displayCode) {
                $result .= "<h1>{$code->value}</h1>";
            }
            $result .= '<h2>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
        } else {
            $timeout = max($code === ApplicationError::Error ? 10000 : 5000, $timeout);
        }
        if ($code === ApplicationError::ServiceUnavailable && $isWebmaster) {
            $result .= '<p><a href="/maintenance/unset" class="btn btn-danger mt-3">'
                . htmlspecialchars($this->languagesDataHelper->translate('error.maintenance.cancel'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</a></p>';
        }
        $navbarColors = $this->dataHelper->getNavbarColors();
        $this->application->getFlight()->response()->status($code->value);

        $viewModel = new InfoViewModel(
            content: $result,
            timer: $timeout,
            previousPage: true,
            layoutParams: [
                'navbarBgColor'   => $navbarColors->bgColor,
                'navbarInkColor'  => $navbarColors->inkColor,
                'navbarIconColor' => $navbarColors->iconColor,
            ],
        );
        $this->emptyController->render('Common/views/info.latte', $viewModel);

        if ($code != ApplicationError::Ok) {
            exit;
        }
        Application::unreachable("Ok isn't an error");
    }

    private function isJsonExpected(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $accept = is_string($accept) ? $accept : '';
        return str_contains($accept, 'application/json');
    }
}
