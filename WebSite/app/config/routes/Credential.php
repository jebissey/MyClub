<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\modules\Common\interfaces\RouteInterface;
use app\modules\Common\valueObjects\Route;
use app\modules\Webmaster\CredentialsController;

final class Credential implements RouteInterface
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    public function __construct(private readonly ControllerFactory $controllerFactory)
    {
    }

    /**
     * @return array<int, Route>
     */
    public function get(): array
    {
        $credentialsController = fn() => $this->controllerFactory->make(CredentialsController::class);

        $this->routes[] = new Route('GET  /sendEmails', $credentialsController, 'sendEmailCredentialsEdit');
        $this->routes[] = new Route('POST /sendEmails/saveCredentials', $credentialsController, 'sendEmailCredentialsSave');

        $this->routes[] = new Route('GET  /turnstile', $credentialsController, 'turnstileCredentialsEdit');
        $this->routes[] = new Route('POST /turnstile/saveCredentials', $credentialsController, 'turnstileCredentialsSave');

        $this->routes[] = new Route('GET  /helloasso', $credentialsController, 'helloassoCredentialsEdit');
        $this->routes[] = new Route('POST /helloasso/saveCredentials', $credentialsController, 'helloassoCredentialsSave');

        return $this->routes;
    }
}
