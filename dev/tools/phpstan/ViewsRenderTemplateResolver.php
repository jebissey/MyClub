<?php

declare(strict_types=1);

namespace app\phpstan;

use Efabrica\PHPStanLatte\Collector\CollectedData\CollectedResolvedNode;
use Efabrica\PHPStanLatte\LatteContext\LatteContext;
use Efabrica\PHPStanLatte\LatteTemplateResolver\LatteTemplateResolverResult;
use Efabrica\PHPStanLatte\LatteTemplateResolver\NodeLatteTemplateResolverInterface;
use Efabrica\PHPStanLatte\Template\Template;
use Efabrica\PHPStanLatte\Template\TemplateContext;
use Efabrica\PHPStanLatte\Template\Variable;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionProperty;

/**
 * Resolves templates rendered through: $this->views->render('Module/views/x.latte', $viewModel->toArray())
 * Template variables are the public properties of the ViewModel.
 */
final class ViewsRenderTemplateResolver implements NodeLatteTemplateResolverInterface
{
    private const PARAM_TEMPLATE = 'template';
    private const PARAM_VIEW_MODEL = 'viewModel';
    private const PARAM_CONTROLLER = 'controller';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly string $modulesDir,
    ) {
    }

    /**
     * Runs on every parsed node: keeps only `->views->render('literal', $vm->toArray())` calls.
     *
     * @return list<CollectedResolvedNode>
     */
    public function collect(Node $node, Scope $scope): array
    {
        if (!$node instanceof MethodCall
            || !$node->name instanceof Identifier
            || $node->name->toString() !== 'render'
            || !$node->var instanceof PropertyFetch
            || !$node->var->name instanceof Identifier
            || $node->var->name->toString() !== 'views'
        ) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) < 2 || !$args[0]->value instanceof String_) {
            return [];
        }

        $data = $args[1]->value;
        if (!$data instanceof MethodCall
            || !$data->name instanceof Identifier
            || $data->name->toString() !== 'toArray'
        ) {
            return [];
        }

        $classNames = $scope->getType($data->var)->getObjectClassNames();
        $classReflection = $scope->getClassReflection();
        if (count($classNames) !== 1 || $classReflection === null) {
            return [];
        }

        return [
            new CollectedResolvedNode(
                static::class,
                $scope->getFile(),
                [
                    self::PARAM_TEMPLATE => $args[0]->value->value,
                    self::PARAM_VIEW_MODEL => $classNames[0],
                    self::PARAM_CONTROLLER => $classReflection->getName(),
                ]
            ),
        ];
    }

    /**
     * Runs once all nodes are collected: builds one Template per render call.
     */
    public function resolve(CollectedResolvedNode $resolvedNode, LatteContext $latteContext): LatteTemplateResolverResult
    {
        $params = $resolvedNode->getParams(); // verify the getter name in CollectedResolvedNode
        $result = new LatteTemplateResolverResult();

        $viewModelReflection = $this->reflectionProvider->getClass((string)$params[self::PARAM_VIEW_MODEL]);

        $variables = [];
        foreach ($viewModelReflection->getNativeReflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $variables[] = new Variable(
                $name,
                $viewModelReflection->getNativeProperty($name)->getReadableType()
            );
        }

        $result->addTemplate(new Template(
            $this->modulesDir . '/' . $params[self::PARAM_TEMPLATE],
            (string)$params[self::PARAM_CONTROLLER],
            'render',
            new TemplateContext($variables)
        ));

        return $result;
    }
}