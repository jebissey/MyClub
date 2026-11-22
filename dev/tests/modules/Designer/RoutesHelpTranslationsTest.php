<?php

declare(strict_types=1);

namespace tests\modules\Designer;

class RoutesHelpTranslationsTest extends RoutesHelpTestCase
{
    public function testEveryHelpKeyTargetsAnExistingControllerMethod(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $names = $this->helpKeys($pdo);
        $this->assertNotEmpty($names, 'No help text found in the Languages table.');

        $sources = $this->indexSources();

        $orphans = [];
        foreach ($names as $name) {
            if (preg_match('/^designer\.routes\.help\.(\w+)\.(\w+)$/', $name, $matches) !== 1) {
                $orphans[] = "{$name} (unexpected key format)";
                continue;
            }
            [, $class, $method] = $matches;

            if (!isset($sources[$class])) {
                $orphans[] = "{$name} (class {$class} not found)";
            } elseif (!$this->classHasMethod($sources, $class, $method)) {
                $orphans[] = "{$name} (method {$method}() not found in {$class})";
            }
        }

        $this->assertSame(
            [],
            $orphans,
            "Help texts of the /routes page pointing to something that no longer exists:\n - "
                . implode("\n - ", $orphans)
        );
    }

    public function testEveryDeclaredRouteHasAHelpText(): void
    {
        $pdo = $this->openDatabaseCopyOrSkip();
        $existing = array_flip($this->helpKeys($pdo));

        $missing = [];
        foreach ($this->declaredRoutes() as $route) {
            $key = self::KEY_PREFIX . $route['class'] . '.' . $route['method'];
            if (!isset($existing[$key])) {
                $missing[$key] = "{$route['class']}.{$route['method']} [{$route['route']}]";
            }
        }

        $this->assertSame(
            [],
            array_values($missing),
            "Routes without a help text (key designer.routes.help.<Class>.<method>):\n - "
                . implode("\n - ", $missing)
        );
    }
}
