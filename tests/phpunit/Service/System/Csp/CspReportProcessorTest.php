<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Csp;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\System\CspReport;
use Inachis\Enum\System\CspSeverity;
use Inachis\Repository\System\CspReportRepository;
use Inachis\Service\System\Csp\CspNoiseFilter;
use Inachis\Service\System\Csp\CspReportDtoFactory;
use Inachis\Service\System\Csp\CspReportProcessor;
use Inachis\Service\System\Csp\CspSeverityResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CspReportProcessorTest extends TestCase
{
    #[Test]
    public function itProcessesALegacyReportAndCreatesAnEntity(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::once())
            ->method('findOneByFingerprint')
            ->willReturn(null);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                function (CspReport $report): bool {
                    self::assertSame(
                        'https://example.com/page',
                        $report->getDocumentUri(),
                    );
                    self::assertSame(
                        'https://cdn.example.com/script.js',
                        $report->getBlockedUri(),
                    );
                    self::assertSame(
                        'cdn.example.com',
                        $report->getHost(),
                    );
                    self::assertSame(
                        'script-src',
                        $report->getEffectiveDirective(),
                    );
                    self::assertSame(
                        'script-src-elem',
                        $report->getViolatedDirective(),
                    );
                    self::assertSame(
                        'report',
                        $report->getDisposition(),
                    );
                    self::assertSame(200, $report->getStatusCode());
                    self::assertSame(
                        "default-src 'self'; script-src 'self'",
                        $report->getOriginalPolicy(),
                    );
                    self::assertSame(
                        'https://example.com/app.js',
                        $report->getSourceFile(),
                    );
                    self::assertSame(42, $report->getLineNumber());
                    self::assertSame(10, $report->getColumnNumber());
                    self::assertSame(
                        'https://example.com/',
                        $report->getReferrer(),
                    );
                    self::assertSame(
                        'Test Browser',
                        $report->getUserAgent(),
                    );
                    self::assertSame(
                        CspSeverity::Critical,
                        $report->getSeverity(),
                    );
                    self::assertSame(1, $report->getOccurrences());
                    self::assertSame(
                        [
                            'document-uri' => 'https://example.com/page',
                            'blocked-uri' => 'https://cdn.example.com/script.js',
                            'effective-directive' => 'script-src',
                            'violated-directive' => 'script-src-elem',
                            'original-policy' => "default-src 'self'; script-src 'self'",
                            'source-file' => 'https://example.com/app.js',
                            'line-number' => 42,
                            'column-number' => 10,
                            'disposition' => 'report',
                            'status-code' => 200,
                        ],
                        $report->getPayload(),
                    );
                    self::assertNotNull($report->getFirstSeenAt());
                    self::assertNotNull($report->getLastSeenAt());

                    return true;
                },
            ));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process(
            [
                'csp-report' => [
                    'document-uri' => 'https://example.com/page',
                    'blocked-uri' => 'https://cdn.example.com/script.js',
                    'effective-directive' => 'script-src',
                    'violated-directive' => 'script-src-elem',
                    'original-policy' => "default-src 'self'; script-src 'self'",
                    'source-file' => 'https://example.com/app.js',
                    'line-number' => 42,
                    'column-number' => 10,
                    'disposition' => 'report',
                    'status-code' => 200,
                ],
            ],
            userAgent: 'Test Browser',
            referrer: 'https://example.com/',
        );
    }

    #[Test]
    public function itProcessesAReportingApiReportAndCreatesAnEntity(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::once())
            ->method('findOneByFingerprint')
            ->willReturn(null);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                function (CspReport $report): bool {
                    self::assertSame(
                        'https://example.com/page',
                        $report->getDocumentUri(),
                    );
                    self::assertSame(
                        'https://api.example.com/data',
                        $report->getBlockedUri(),
                    );
                    self::assertSame(
                        'api.example.com',
                        $report->getHost(),
                    );
                    self::assertSame(
                        'connect-src',
                        $report->getEffectiveDirective(),
                    );
                    self::assertSame(
                        'connect-src',
                        $report->getViolatedDirective(),
                    );
                    self::assertSame(
                        'enforce',
                        $report->getDisposition(),
                    );
                    self::assertNull($report->getStatusCode());
                    self::assertSame(
                        CspSeverity::High,
                        $report->getSeverity(),
                    );
                    self::assertSame(1, $report->getOccurrences());

                    return true;
                },
            ));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process(
            [
                [
                    'age' => 0,
                    'type' => 'csp-violation',
                    'url' => 'https://example.com/page',
                    'user_agent' => 'Browser',
                    'body' => [
                        'blockedURL' => 'https://api.example.com/data',
                        'disposition' => 'enforce',
                        'effectiveDirective' => 'connect-src',
                        'originalPolicy' => "default-src 'self'",
                        'statusCode' => 0,
                        'documentURL' => 'https://example.com/page',
                        'violatedDirective' => 'connect-src',
                    ],
                ],
            ],
            userAgent: 'Test Browser',
            referrer: 'https://example.com/',
        );
    }

    #[Test]
    public function itProcessesMultipleReportingApiReportsAndIgnoresOtherReportTypes(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::once())
            ->method('findOneByFingerprint')
            ->willReturn(null);

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(CspReport::class));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process([
            null,
            [
                'age' => 1,
                'type' => 'deprecation',
                'url' => 'https://example.com',
                'user_agent' => 'Browser',
                'body' => [],
            ],
            [
                'age' => 2,
                'type' => 'csp-violation',
                'url' => 'https://example.com',
                'user_agent' => 'Browser',
                'body' => [
                    'blockedURL' => 'https://cdn.example.com/style.css',
                    'effectiveDirective' => 'style-src',
                    'violatedDirective' => 'style-src',
                    'originalPolicy' => "default-src 'self'",
                    'disposition' => 'report',
                    'statusCode' => 0,
                ],
            ],
        ]);
    }

    #[Test]
    public function itIgnoresNonListPayloadsWithoutLegacyReports(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::never())
            ->method('findOneByFingerprint');

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process([
            'unexpected' => 'payload',
        ]);
    }

    #[Test]
    public function itIgnoresNoiseReports(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::never())
            ->method('findOneByFingerprint');

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process([
            'csp-report' => [
                'document-uri' => 'https://example.com',
                'blocked-uri' => 'chrome-extension://abcdefghijkl/script.js',
                'effective-directive' => 'script-src',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'",
                'status-code' => 200,
            ],
        ]);
    }

    #[Test]
    public function itUpdatesAnExistingReport(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $existing = new CspReport();
        $existing->setOccurrences(4);

        $repository
            ->expects(self::once())
            ->method('findOneByFingerprint')
            ->willReturn($existing);

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process([
            'csp-report' => [
                'document-uri' => 'https://example.com',
                'blocked-uri' => 'https://cdn.example.com/script.js',
                'effective-directive' => 'script-src',
                'violated-directive' => 'script-src',
                'original-policy' => "default-src 'self'",
                'status-code' => 200,
            ],
        ]);

        self::assertSame(5, $existing->getOccurrences());
        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $existing->getLastSeenAt(),
        );
    }

    #[Test]
    public function itFlushesWhenThereAreNoReports(): void
    {
        $repository = $this->createMock(CspReportRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository
            ->expects(self::never())
            ->method('findOneByFingerprint');

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $processor = $this->createProcessor(
            $entityManager,
            $repository,
        );

        $processor->process([]);
    }

    private function createProcessor(
        EntityManagerInterface $entityManager,
        CspReportRepository $repository,
    ): CspReportProcessor {
        return new CspReportProcessor(
            entityManager: $entityManager,
            repository: $repository,
            dtoFactory: new CspReportDtoFactory(),
            noiseFilter: new CspNoiseFilter(),
            severityResolver: new CspSeverityResolver(),
        );
    }
}
