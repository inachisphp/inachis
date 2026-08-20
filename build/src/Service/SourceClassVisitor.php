<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Service;

use Inachis\Build\Enum\SourceClassType;
use Inachis\Build\Model\SourceClass;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Name;

final class SourceClassVisitor extends NodeVisitorAbstract
{
    /**
     * @var SourceClass[]
     */
    private array $classes = [];

    private ?Node $currentClassNode = null;

    private bool $hasConstructor = false;

    private bool $hasRequiredConstructorParameters = false;

    private ?string $namespace = null;

    public function __construct(
        private readonly string $projectDirectory,
        private readonly string $sourceFile,
        private readonly string $relativePath,
    ) {
    }

    /**
     * @return SourceClass[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    public function enterNode(
        Node $node
    ): ?int {
        if ($node instanceof Namespace_) {
            $this->namespace = $node->name?->toString();

            return null;
        }

        if (
            $node instanceof Class_
            || $node instanceof Interface_
            || $node instanceof Trait_
            || $node instanceof Enum_
        ) {
            $this->currentClassNode = $node;

            $this->hasConstructor = false;
            $this->hasRequiredConstructorParameters = false;

            return null;
        }

        if (
            $node instanceof ClassMethod
            && $node->name->toString() === '__construct'
        ) {
            $this->hasConstructor = true;

            foreach ($node->params as $parameter) {
                if ($parameter->default === null) {
                    $this->hasRequiredConstructorParameters = true;

                    break;
                }
            }
        }

        return null;
    }

    private function createSourceClass(
        Node $node
    ): SourceClass {
        $type = $this->getType($node);

        $name = $node->name?->toString();

        if ($name === null) {
            throw new \RuntimeException(
                'Unable to determine class name.'
            );
        }

        return new SourceClass(
            shortName: $name,
            namespace: $this->namespace ?? '',
            sourceFile: $this->sourceFile,
            relativePath: $this->relativePath,
            expectedTestFile: $this->buildExpectedTestFile(),
            abstract: $this->isAbstract($node),
            readonly: $this->isReadonly($node),
            final: $this->isFinal($node),
            extends: $this->getExtends($node),
            type: $type,
            hasConstructor: $this->hasConstructor,
            hasRequiredConstructorParameters: $this->hasRequiredConstructorParameters,
        );
    }

    public function leaveNode(
        Node $node
    ): ?int {
        if ($node === $this->currentClassNode) {
            $this->classes[] = $this->createSourceClass($node);

            $this->currentClassNode = null;
        }

        return null;
    }
        
    private function getType(
        Node $node
    ): SourceClassType {
        return match (true) {
            $node instanceof Interface_ => SourceClassType::Interface,
            $node instanceof Trait_ => SourceClassType::Trait,
            $node instanceof Enum_ => SourceClassType::Enum,
            default => SourceClassType::ConcreteClass,
        };
    }

    private function isAbstract(
        Node $node
    ): bool {
        return $node instanceof Class_
            && $node->isAbstract();
    }

    private function isReadonly(
        Node $node
    ): bool {
        return $node instanceof Class_
            && $node->isReadonly();
    }

    private function isFinal(
        Node $node
    ): bool {
        return $node instanceof Class_
            && $node->isFinal();
    }

    private function getExtends(
        Node $node
    ): ?string {
        if (!$node instanceof Class_) {
            return null;
        }

        if ($node->extends === null) {
            return null;
        }

        return $this->resolveName($node->extends);
    }

    private function resolveName(
        Name $name
    ): string {
        /** @var Name|null $resolved */
        $resolved = $name->getAttribute('resolvedName');

        return ($resolved ?? $name)->toString();
    }

    private function buildExpectedTestFile(): string
    {
        $relativeTestPath = preg_replace(
            '/\.php$/',
            'Test.php',
            $this->relativePath
        );

        if ($relativeTestPath === null) {
            throw new \RuntimeException(
                'Unable to build test filename.'
            );
        }

        return sprintf(
            '%s/tests/phpunit/%s',
            $this->projectDirectory,
            $relativeTestPath
        );
    }
}