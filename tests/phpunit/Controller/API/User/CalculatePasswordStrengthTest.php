<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\User;

use Doctrine\ORM\EntityManager;
use Inachis\Controller\API\User\CalculatePasswordStrength;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\TestCase;

final class CalculatePasswordStrengthTest extends InachisControllerTestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CalculatePasswordStrength(
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
        );

        self::assertInstanceOf(
            CalculatePasswordStrength::class,
            $instance,
        );
    }
}
