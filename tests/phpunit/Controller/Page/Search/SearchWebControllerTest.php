<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Search;

use Inachis\Controller\Page\Search\SearchWebController;
use PHPUnit\Framework\TestCase;

final class SearchWebControllerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new SearchWebController();

        self::assertInstanceOf(
            SearchWebController::class,
            $instance
        );
    }
}