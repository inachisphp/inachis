<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content\Page;

use Inachis\Service\Content\Page\RevisionDiffRenderer;
use PHPUnit\Framework\TestCase;

final class RevisionDiffRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new RevisionDiffRenderer();

        self::assertInstanceOf(
            RevisionDiffRenderer::class,
            $instance,
        );
    }
}
