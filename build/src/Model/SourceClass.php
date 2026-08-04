<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Model;

use Inachis\Build\Enum\SourceClassType;

final readonly class SourceClass
{
    public function __construct(
        private string $shortName,
        private string $namespace,
        private string $sourceFile,
        private string $relativePath,
        private string $expectedTestFile,
        private bool $abstract = false,
        private bool $readonly = false,
        private bool $final = false,
        private ?string $extends = null,
        private SourceClassType $type,
        private bool $hasConstructor = false,
        private bool $hasRequiredConstructorParameters = false,
    ) {
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->namespace . '\\' . $this->shortName;
    }

    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    public function getExpectedTestFile(): string
    {
        return $this->expectedTestFile;
    }

    public function getTestShortName(): string
    {
        return $this->shortName . 'Test';
    }

    public function getTestNamespace(): string
    {
        return str_replace(
            'Inachis\\',
            'Inachis\\Tests\\phpunit\\',
            $this->namespace
        );
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isInterface(): bool
    {
        return $this->type === SourceClassType::Interface;
    }

    public function isTrait(): bool
    {
        return $this->type === SourceClassType::Trait;
    }

    public function isEnum(): bool
    {
        return $this->type === SourceClassType::Enum;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }

    public function getExtends(): ?string
    {
        return $this->extends;
    }

    public function getType(): SourceClassType
    {
        return $this->type;
    }

    public function hasConstructor(): bool
    {
        return $this->hasConstructor;
    }

    public function hasRequiredConstructorParameters(): bool
    {
        return $this->hasRequiredConstructorParameters;
    }

    public function hasTest(): bool
    {
        return is_file($this->expectedTestFile);
    }

    public function extends(string $class): bool
    {
        return $this->extends === $class;
    }

    public function shouldGenerateTest(): bool
    {
        return $this->type === SourceClassType::ConcreteClass 
            && !$this->abstract;
    }

    public function getTestFullyQualifiedClassName(): string
    {
        return sprintf(
            '%s\\%s',
            $this->getTestNamespace(),
            $this->getTestShortName(),
        );
    }
}
