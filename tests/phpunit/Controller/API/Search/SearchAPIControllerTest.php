<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\Search;

use Inachis\Controller\API\Search\SearchAPIController;
use PHPUnit\Framework\TestCase;

final class SearchAPIControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SearchAPIController();

        self::assertInstanceOf(
            SearchAPIController::class,
            $instance,
        );
    }
}
