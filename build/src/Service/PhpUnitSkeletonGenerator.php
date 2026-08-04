<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Service;

use Inachis\Build\Model\SourceClass;

final class PhpUnitSkeletonGenerator
{
    public function generate(
        SourceClass $sourceClass
    ): string {
        return sprintf(
            <<<'PHP'
<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace %s;

use %s;
use PHPUnit\Framework\TestCase;

final class %s extends TestCase
{
%s
}
PHP,
            $sourceClass->getTestNamespace(),
            $sourceClass->getFullyQualifiedClassName(),
            $sourceClass->getTestShortName(),
            $this->buildTestMethods($sourceClass),
        );
    }

    private function buildTestMethods(
        SourceClass $sourceClass
    ): string {
        if (!$sourceClass->shouldGenerateTest()) {
            return $this->placeholder();
        }

        if ($sourceClass->hasRequiredConstructorParameters()) {
            return $this->placeholder();
        }

        return $this->instantiationTest($sourceClass);
    }

    private function instantiationTest(
        SourceClass $sourceClass
    ): string {
        $shortName = $sourceClass->getShortName();

        return sprintf(
            <<<'PHP'

    public function testCanBeInstantiated(): void
    {
        $instance = new %s();

        self::assertInstanceOf(
            %s::class,
            $instance
        );
    }
PHP,
            $shortName,
            $shortName,
        );
    }
   
    private function placeholder(): string
    {
        return <<<'PHP'

    public function testPlaceholder(): void
    {
        $this->markTestIncomplete(
            'Test not implemented.'
        );
    }
PHP;
    }
}