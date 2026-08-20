<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\System;

use Inachis\Command\System\GenerateSitemapCommand;
use Inachis\Service\Discovery\Generator\SitemapGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateSitemapCommandTest extends TestCase
{
    private string $projectDir = '/tmp/inachis_sitemap_test';

    public function testExecuteSuccess(): void
    {
        $generator = $this->createSitemapGenerator();
        $command = new GenerateSitemapCommand($generator, $this->projectDir);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString(
            'Sitemap generated in /tmp/inachis_sitemap_test/public/sitemap.xml',
            $tester->getDisplay(),
        );
    }

    public function testExecuteFailureWhenGeneratorThrowsException(): void
    {
        $generator = $this->createSitemapGenerator(new \RuntimeException('Database connection failed'));
        $command = new GenerateSitemapCommand($generator, $this->projectDir);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString(
            'Failed to generate sitemap: Database connection failed',
            $tester->getDisplay(),
        );
    }

    /**
     * Helper to safely instantiate or stub SitemapGenerator whether it is a normal or final class.
     */
    private function createSitemapGenerator(?\Throwable $exceptionToThrow = null): SitemapGenerator
    {
        $reflection = new \ReflectionClass(SitemapGenerator::class);

        if (!$reflection->isFinal()) {
            $generator = $this->createStub(SitemapGenerator::class);
            if (null !== $exceptionToThrow) {
                $generator->method('generate')->willThrowException($exceptionToThrow);
            }

            return $generator;
        }

        return $reflection->newInstanceWithoutConstructor();
    }
}
