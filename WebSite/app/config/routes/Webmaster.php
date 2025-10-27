<?php

declare(strict_types=1);

namespace app\config\routes;

use app\config\ControllerFactory;
use app\interfaces\RouteInterface;
use app\valueObjects\Route;

class Webmaster implements RouteInterface
{
    private array $routes = [];

    public function __construct(private ControllerFactory $controllerFactory) {}

    public function get(): array
    {
        $webmasterController = fn() => $this->controllerFactory->makeWebmasterController();

        $this->routes[] = new Route('GET  /admin', $webmasterController, 'homeAdmin');
        $this->routes[] = new Route('GET  /admin/help', $webmasterController, 'helpAdmin');
        $this->routes[] = new Route('GET  /admin/webmaster/help', $webmasterController, 'helpWebmaster');
        $this->routes[] = new Route('GET  /sendEmails', $webmasterController, 'sendEmailCredentialsEdit');
        $this->routes[] = new Route('POST /sendEmails/saveCredentials', $webmasterController, 'sendEmailCredentialsSave');        
        $this->routes[] = new Route('GET  /installations', $webmasterController, 'showInstallations');
        $this->routes[] = new Route('GET  /notifications', $webmasterController, 'notifications');
        $this->routes[] = new Route('GET  /sitemap.xml', $webmasterController, 'sitemapGenerator');
        $this->routes[] = new Route('GET  /webmaster', $webmasterController, 'homeWebmaster');

        return $this->routes;
    }
}
