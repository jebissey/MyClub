<?php

declare(strict_types=1);

namespace app\modules\Designer;

use app\enums\FilterInputRule;
use app\helpers\WebApp;
use app\modules\Common\TableController;
use app\modules\Common\valueObjects\Route;
use app\modules\Designer\viewModels\DesignerHomeViewModel;
use app\modules\Designer\viewModels\DesignerRoutesViewModel;

/**
 * @phpstan-type RouteRow array{method: string, uri: string, module: string, function: string}
 */
final class DesignerController extends TableController
{
    public function help(): void
    {
        $this->renderHelp('Help_Designer', fn($u) => $u->isDesigner(), __FILE__, __LINE__);
    }

    public function homeDesigner(): void
    {
        if ($this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner(), __FILE__, __LINE__)) {
            $_SESSION['navbar'] = 'designer';

            $viewModel = new DesignerHomeViewModel(
                content: '',
                layoutParams: $this->getAllParams([
                    'page' => $this->application->getConnectedUser()->getPage(),
                ]),
            );

            $this->views->render('Designer/views/designer.latte', $viewModel);
        }
    }

    public function showRoutes(): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        $schema = [
            'method'   => FilterInputRule::Text->value,
            'uri'      => FilterInputRule::Text->value,
            'module'   => FilterInputRule::Text->value,
            'function' => FilterInputRule::Text->value,
        ];
        $rawFilterValues = WebApp::filterInput($schema, $this->flight->request()->query->getData());

        /** @var array<string, string> $filterValues */
        $filterValues = [];
        foreach (array_keys($schema) as $field) {
            $value = $rawFilterValues[$field] ?? null;
            $filterValues[$field] = is_string($value) ? $value : '';
        }

        $labels = [
            'method'   => ($this->t)('designer.routes.label.method'),
            'uri'      => ($this->t)('designer.routes.label.uri'),
            'module'   => ($this->t)('designer.routes.label.module'),
            'function' => ($this->t)('designer.routes.label.function'),
        ];

        $filterConfig = [];
        $columns = [];
        foreach ($labels as $field => $label) {
            $filterConfig[] = ['name' => $field, 'label' => $label];
            $columns[] = ['field' => $field, 'label' => $label];
        }

        $data = $this->prepareTableDataFromArray($this->collectConfiguredRoutes(), $filterValues);

        $viewModel = new DesignerRoutesViewModel(
            routes: $data['items'],
            currentPage: $data['currentPage'],
            totalPages: $data['totalPages'],
            filterValues: $filterValues,
            filters: $filterConfig,
            columns: $columns,
            resetUrl: '/routes',
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
            ]),
        );

        $this->views->render('Designer/views/routes.latte', $viewModel);
    }

    /**
     * Builds one row per route declared in app/config/routes, sorted by URI then method.
     *
     * @return list<RouteRow>
     */
    private function collectConfiguredRoutes(): array
    {
        $rows = [];
        foreach ($this->getConfiguredRoutes() as $route) {
            $rows[] = $this->toRow($route);
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => [$a['uri'], $a['method']] <=> [$b['uri'], $b['method']]
        );

        return $rows;
    }

    /**
     * Routes registered by Routes::add(), shared through the Flight engine.
     *
     * @return list<Route>
     */
    private function getConfiguredRoutes(): array
    {
        $stored = $this->flight->get('configuredRoutes');
        if (!is_array($stored)) {
            return [];
        }

        return array_values(array_filter(
            $stored,
            static fn(mixed $route): bool => $route instanceof Route
        ));
    }

    /**
     * @return RouteRow
     */
    private function toRow(Route $route): array
    {
        // 'GET|POST  /admin' => methods 'GET|POST', uri '/admin'
        $parts = preg_split('/\s+/', trim($route->methodAndPath), 2) ?: [];
        $method = str_replace('|', ' | ', $parts[0] ?? '');
        $uri = $parts[1] ?? '/';

        // The route holds a factory closure: calling it gives the controller instance
        $controller = ($route->controllerFactory)();
        $class = is_object($controller) ? $controller::class : '';

        $module = $this->resolveModule($class);

        $shortClass = $class === '' ? '' : (new \ReflectionClass($class))->getShortName();

        return [
            'method'   => $method,
            'uri'      => $uri,
            'module'   => $module,
            'function' => $shortClass === '' ? $route->function : $shortClass . '::' . $route->function,
        ];
    }

    /**
     * app\modules\Webmaster\WebmasterController => Webmaster
     * app\apis\EventAttributeApi                => Api
     */
    private function resolveModule(string $class): string
    {
        if (preg_match('/^app\\\\modules\\\\([^\\\\]+)\\\\/', $class, $matches) === 1) {
            return $matches[1];
        }

        if (str_starts_with($class, 'app\\apis\\')) {
            return 'Api';
        }

        return '';
    }
}
