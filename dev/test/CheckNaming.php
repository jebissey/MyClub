<?php

declare(strict_types=1);

/**
 * Checks PHP naming conventions in WebSite/app, with PHPStan-like levels.
 *
 * Usage: php dev/test/CheckNaming.php [--level=N]   (default: 2)
 *
 * Level 0: class-likes in PascalCase, methods in camelCase, constants in UPPER_SNAKE_CASE,
 *          enum cases in PascalCase
 * Level 1: + properties and promoted constructor properties in camelCase
 * Level 2: + parameters and local variables in camelCase
 * Level 3: + file name matches class name, enums live in app/enums
 * Level 4: + 'View' suffix forbidden for value objects (use 'Info')
 *
 * Requires: composer require --dev nikic/php-parser (v5)
 */

require __DIR__ . '/../../WebSite/vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

const PASCAL_CASE = '/^[A-Z][a-zA-Z0-9]*$/';
const CAMEL_CASE = '/^[a-z][a-zA-Z0-9]*$/';
const UPPER_SNAKE_CASE = '/^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*$/';

final class NamingVisitor extends NodeVisitorAbstract
{
    /** @var array<string, string> */
    public array $violations = [];

    public function __construct(
        private readonly string $file,
        private readonly string $relativePath,
        private readonly int $maxLevel,
    ) {}

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\ClassLike && $node->name !== null) {
            $this->checkClassLike($node);
        } elseif ($node instanceof Node\Stmt\ClassMethod) {
            // Magic methods (__construct, __toString...) are not subject to the convention
            if (!str_starts_with($node->name->toString(), '__')) {
                $this->check(0, $node, $node->name->toString(), CAMEL_CASE, 'method');
            }
        } elseif ($node instanceof Node\Stmt\ClassConst) {
            foreach ($node->consts as $const) {
                $this->check(0, $const, $const->name->toString(), UPPER_SNAKE_CASE, 'constant');
            }
        } elseif ($node instanceof Node\Stmt\EnumCase) {
            $this->check(0, $node, $node->name->toString(), PASCAL_CASE, 'enum case');
        } elseif ($node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $this->check(1, $prop, $prop->name->toString(), CAMEL_CASE, 'property');
            }
        } elseif ($node instanceof Node\Param && $node->var instanceof Node\Expr\Variable) {
            $variable = $node->var;
            // Mark the variable so it is not reported a second time as a local variable
            $variable->setAttribute('isParamVariable', true);

            $name = $variable->name;
            if (is_string($name)) {
                $promoted = $node->isPromoted();
                $this->check(
                    $promoted ? 1 : 2,
                    $node,
                    $name,
                    CAMEL_CASE,
                    $promoted ? 'promoted property' : 'parameter'
                );
            }
        } elseif ($node instanceof Node\Expr\Variable) {
            $name = $node->name;
            // $this, superglobals ($_GET, $_SERVER...) and $GLOBALS are not subject to the convention
            if (
                is_string($name)
                && $node->getAttribute('isParamVariable') !== true
                && $name !== 'this'
                && $name !== 'GLOBALS'
                && !str_starts_with($name, '_')
            ) {
                $this->check(2, $node, $name, CAMEL_CASE, 'variable');
            }
        }

        return null;
    }

    private function checkClassLike(Node\Stmt\ClassLike $node): void
    {
        $identifier = $node->name;
        if ($identifier === null) {
            return; // Anonymous class
        }
        $name = $identifier->toString();
        $this->check(0, $node, $name, PASCAL_CASE, 'class-like name');

        if ($this->maxLevel >= 3) {
            if ($name !== pathinfo($this->file, PATHINFO_FILENAME)) {
                $this->add($node, 3, "class '$name' does not match its file name");
            }
            if ($node instanceof Node\Stmt\Enum_ && !str_contains($this->relativePath, 'app/enums/')) {
                $this->add($node, 3, "enum '$name' must be in app/enums");
            }
        }

        if (
            $this->maxLevel >= 4
            && str_contains($this->relativePath, '/valueObjects/')
            && str_ends_with($name, 'View')
        ) {
            $this->add($node, 4, "suffix 'View' is forbidden for value objects ('$name'), use 'Info'");
        }
    }

    private function check(int $level, Node $node, string $name, string $pattern, string $kind): void
    {
        if ($level <= $this->maxLevel && preg_match($pattern, $name) !== 1) {
            $this->add($node, $level, "$kind '$name' does not follow the naming convention");
        }
    }

    private function add(Node $node, int $level, string $message): void
    {
        $key = $node->getStartLine() . ':' . $message;
        $this->violations[$key] = sprintf(
            '%s:%d [level %d] %s',
            $this->relativePath,
            $node->getStartLine(),
            $level,
            $message
        );
    }
}

// --- Entry point -------------------------------------------------------------

$options = getopt('', ['level::']);
$level = (int) ($options['level'] ?? 2);
$root = realpath(__DIR__ . '/../../WebSite');
if ($root === false) {
    fwrite(STDERR, "WebSite directory not found\n");
    exit(2);
}

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/app', FilesystemIterator::SKIP_DOTS)
);

$violations = [];
foreach ($files as $file) {
    if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = str_replace('\\', '/', substr($path, strlen($root) + 1));

    // Same exclusions as phpcs.xml
    if (preg_match('#/(migrators|sounds|images)/#', '/' . $relativePath) === 1) {
        continue;
    }

    $statements = $parser->parse((string) file_get_contents($path)) ?? [];
    $visitor = new NamingVisitor($path, $relativePath, $level);
    $traverser = new NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($statements);

    array_push($violations, ...array_values($visitor->violations));
}

foreach ($violations as $line) {
    fwrite(STDERR, $line . PHP_EOL);
}

if ($violations !== []) {
    fwrite(STDERR, sprintf("%d naming violation(s) at level %d\n", count($violations), $level));
    exit(1);
}

fwrite(STDERR, "Naming conventions OK (level $level)\n");
