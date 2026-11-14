<?php

declare(strict_types=1);

namespace app\modules\Designer;

use Closure;
use app\modules\Common\AbstractController;
use app\modules\Common\valueObjects\TableRow;
use app\modules\Designer\viewModels\DesignerHomeViewModel;
use app\modules\Designer\viewModels\DesignerRoutesViewModel;

/**
 * @phpstan-type RouteRow array{method: string, uri: string, module: string, function: string}
 */
final class DesignerController extends AbstractController
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
        if (!$this->userIsAllowedAndMethodIsGood('GET', fn($u) => $u->isDesigner(), __FILE__, __LINE__)) {
            return;
        }

        $connectedUser = $this->application->getConnectedUser();

        /** @var list<RouteRow> $rawRoutes */
        $rawRoutes = [];
        foreach ($this->flight->router()->getRoutes() as $route) {
            [$module, $function] = $this->resolveCallback($route->callback);

            $rawRoutes[] = [
                'method'   => implode(' | ', $route->methods),
                'uri'      => $route->pattern,
                'module'   => $module,
                'function' => $function,
            ];
        }

        // Sort by URI first, then by HTTP method
        usort(
            $rawRoutes,
            fn(array $a, array $b): int => [$a['uri'], $a['method']] <=> [$b['uri'], $b['method']]
        );

        /** @var list<TableRow> $routes */
        $routes = [];
        foreach ($rawRoutes as $index => $row) {
            $routes[] = new TableRow(id: (string) $index, cells: $row);
        }

        $columns = [
            ['field' => 'method',   'label' => ($this->t)('designer.routes.label.method')],
            ['field' => 'uri',      'label' => ($this->t)('designer.routes.label.uri')],
            ['field' => 'module',   'label' => ($this->t)('designer.routes.label.module')],
            ['field' => 'function', 'label' => ($this->t)('designer.routes.label.function')],
        ];

        $viewModel = new DesignerRoutesViewModel(
            routes: $routes,
            currentPage: 1,
            totalPages: 1,
            filterValues: [],
            filters: [],
            columns: $columns,
            resetUrl: '/designer/routes',
            layoutParams: $this->getAllParams([
                'page' => $connectedUser->getPage(),
            ]),
        );

        $this->views->render('Designer/views/routes.latte', $viewModel);
    }

    /**
     * Extracts the module name and the method name from a route callback.
     *
     * @return array{0: string, 1: string} [module, function]
     */
    private function resolveCallback(mixed $callback): array
    {
        $class = '';
        $function = '';

        if (is_array($callback) && count($callback) === 2) {
            $target = $callback[0] ?? null;
            $method = $callback[1] ?? null;

            if (is_object($target)) {
                $class = $target::class;
            } elseif (is_string($target)) {
                $class = $target;
            }
            if (is_string($method)) {
                $function = $method;
            }
        } elseif (is_string($callback) && str_contains($callback, '::')) {
            [$class, $function] = explode('::', $callback, 2);
        } elseif ($callback instanceof Closure) {
            return ['', 'Closure'];
        } elseif (is_string($callback)) {
            return ['', $callback];
        }

        // app\modules\Designer\DesignerController => Designer
        $module = preg_match('/^app\\\\modules\\\\([^\\\\]+)\\\\/', $class, $matches) === 1
            ? $matches[1]
            : '';

        return [$module, $function];
    }
}
