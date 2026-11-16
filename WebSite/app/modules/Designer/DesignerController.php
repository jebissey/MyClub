<?php

declare(strict_types=1);

namespace app\modules\Designer;

use ReflectionClass;
use app\enums\FilterInputRule;
use app\modules\Common\TableController;
use app\modules\Common\valueObjects\Route;
use app\modules\Common\valueObjects\TableRow;
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

    public function routesHelp(): void
    {
        $this->renderHelp('Help_Routes', fn($u) => $u->isAnybody(), __FILE__, __LINE__);
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
        $filterValues = $this->readFilterValues($schema);

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

        $rawRows = $this->collectConfiguredRoutes();
        $filtered = array_values(array_filter(
            $rawRows,
            static function (array $row) use ($filterValues): bool {
                foreach ($filterValues as $key => $value) {
                    if ($value === '') {
                        continue;
                    }
                    if (mb_stripos($row[$key] ?? '', $value) === false) {
                        return false;
                    }
                }
                return true;
            }
        ));

        $itemsPerPage = 10;
        $tablePage = $this->flight->request()->query['tablePage'] ?? null;
        $page = is_numeric($tablePage) ? (int) $tablePage : 1;
        $totalPages = (int) ceil(count($filtered) / $itemsPerPage);
        $currentPage = max(1, min($page, $totalPages));
        $offset = ($currentPage - 1) * $itemsPerPage;

        $items = [];
        foreach (array_slice($filtered, $offset, $itemsPerPage) as $row) {
            $items[] = new TableRow($row['function'], $row);
        }

        $viewModel = new DesignerRoutesViewModel(
            routes: $items,
            currentPage: $currentPage,
            totalPages: $totalPages,
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

    public function showRouteHelp(string $function): void
    {
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isAnybody(), __FILE__, __LINE__)) {
            return;
        }

        $key = 'designer.routes.help.' . str_replace('::', '.', $function);

        $this->renderHelp($key, fn($u) => $u->isAnybody(), __FILE__, __LINE__);
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

        $shortClass = $class === '' ? '' : (new ReflectionClass($class))->getShortName();

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
