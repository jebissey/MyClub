<?php

declare(strict_types=1);

namespace tests\modules\Designer;

use FilesystemIterator;
use PDO;
use PDOStatement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use tests\models\DataHelperTestCase;

abstract class RoutesHelpTestCase extends DataHelperTestCase
{
    protected const KEY_PREFIX = 'designer.routes.help.';

    protected function appRoot(): string
    {
        $root = dirname(__DIR__, 4) . '/WebSite/app';
        $this->assertDirectoryExists($root);

        return $root;
    }

    /**
     * @return list<string> Every help key stored in the Languages table.
     */
    protected function helpKeys(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT Name FROM Languages WHERE Name LIKE '" . self::KEY_PREFIX . "%' ORDER BY Name"
        );
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        /** @var list<string> $names */
        $names = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $names;
    }

    /**
     * Source code of every PHP file under WebSite/app, indexed by short class name.
     *
     * @return array<string, list<string>>
     */
    protected function indexSources(): array
    {
        $sources = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appRoot(), FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }
            // Route providers share their short name with the API classes they expose
            if (str_contains($file->getPathname(), '/config/routes/')) {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if ($code !== false) {
                $sources[$file->getBasename('.php')][] = $code;
            }
        }

        return $sources;
    }

    /**
     * True when the class, one of its parents or one of its traits declares the method.
     *
     * @param array<string, list<string>> $sources
     * @param array<string, true> $visited
     */
    protected function classHasMethod(array $sources, string $class, string $method, array $visited = []): bool
    {
        if (isset($visited[$class]) || !isset($sources[$class])) {
            return false;
        }
        $visited[$class] = true;

        $pattern = '/function\s+' . preg_quote($method, '/') . '\s*\(/';
        foreach ($sources[$class] as $code) {
            if (preg_match($pattern, $code) === 1) {
                return true;
            }
            foreach ($this->parentNames($code) as $parent) {
                if ($this->classHasMethod($sources, $parent, $method, $visited)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Routes declared in app/config/routes/*.php, as [help key class, method, "VERB /path"].
     *
     * @return list<array{class: string, method: string, route: string}>
     */
    protected function declaredRoutes(): array
    {
        $routes = [];
        $files = glob($this->appRoot() . '/config/routes/*.php');
        $this->assertNotEmpty($files, 'No route file found.');

        foreach ($files ?: [] as $file) {
            $code = file_get_contents($file);
            $this->assertNotFalse($code);

            // "use app\apis\ArticleApi as ArticleApiEndpoint;" => the real class is ArticleApi
            $aliases = [];
            preg_match_all('~^use\s+[\w\\\\]*?(\w+)\s+as\s+(\w+);~m', $code, $uses, PREG_SET_ORDER);
            foreach ($uses as $use) {
                $aliases[$use[2]] = $use[1];
            }

            // Variable => controller class, for both styles used by the route files:
            //   $handler = fn() => $this->controllerFactory->make(EventListController::class);
            //   $handler = fn() => $this->apiFactory->makeEventApi();   (legacy)
            $classByVariable = [];
            preg_match_all(
                '~\$(\w+)\s*=\s*fn\s*\(\)\s*=>\s*\$this->\w+->make\(\s*(\w+)::class\s*\)~',
                $code,
                $modern,
                PREG_SET_ORDER
            );
            foreach ($modern as $match) {
                $classByVariable[$match[1]] = $match[2];
            }
            preg_match_all(
                '~\$(\w+)\s*=\s*fn\s*\(\)\s*=>\s*\$this->\w+->make(\w+)\(\)~',
                $code,
                $legacy,
                PREG_SET_ORDER
            );
            foreach ($legacy as $match) {
                $classByVariable[$match[1]] = $match[2];
            }

            $declared = preg_match_all('~new\s+Route\(~', $code);
            preg_match_all(
                '~new\s+Route\(\s*\x27([^\x27]+)\x27\s*,\s*\$(\w+)\s*,\s*\x27(\w+)\x27~',
                $code,
                $parsed,
                PREG_SET_ORDER
            );
            $this->assertCount(
                (int) $declared,
                $parsed,
                basename($file) . ': some "new Route(...)" calls use a shape this test cannot read.'
            );

            foreach ($parsed as [, $methodAndPath, $variable, $method]) {
                $this->assertArrayHasKey(
                    $variable,
                    $classByVariable,
                    basename($file) . ": cannot find the controller of \${$variable}."
                );
                $routes[] = [
                    'class' => $aliases[$classByVariable[$variable]] ?? $classByVariable[$variable],
                    'method' => $method,
                    'route' => (string) preg_replace('/\s+/', ' ', $methodAndPath),
                ];
            }
        }

        return $routes;
    }

    /**
     * Names of the parent class and of the traits used by a class source.
     *
     * @return list<string>
     */
    private function parentNames(string $code): array
    {
        $names = [];
        if (preg_match('/class\s+\w+\s+extends\s+\\\\?(?:\w+\\\\)*(\w+)/', $code, $match) === 1) {
            $names[] = $match[1];
        }
        // Trait imports are indented inside the class body, unlike namespace imports
        if (preg_match_all('/^[ \t]+use\s+([\w\\\\,\s]+);/m', $code, $matches) > 0) {
            foreach ($matches[1] as $list) {
                foreach (explode(',', $list) as $trait) {
                    $parts = explode('\\', trim($trait));
                    $names[] = end($parts);
                }
            }
        }

        return $names;
    }
}
