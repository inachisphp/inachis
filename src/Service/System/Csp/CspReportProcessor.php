<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\System\CspReport;
use Inachis\Model\System\CspReportDto;
use Inachis\Repository\System\CspReportRepository;

/**
 * Processor for CSP reports.
 */
final readonly class CspReportProcessor
{
    /**
     * Constructor for CspReportProcessor.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CspReportRepository $repository,
        private CspReportDtoFactory $dtoFactory,
        private CspNoiseFilter $noiseFilter,
        private CspSeverityResolver $severityResolver,
    ) {
    }

    /**
     * Creates DTOs from the supplied payload.
     *
     * @param array{csp-report: array{
     *     document-uri:string,
     *     referrer?: string,
     *     violated-directive: string,
     *     effective-directive: string,
     *     original-policy: string,
     *     blocked-uri: string,
     *     status-code: int
     * }}|list<array{
     *     age: int,
     *     type: string,
     *     url: string,
     *     user_agent: string,
     *     body: array{
     *         blockedUrl: string,
     *         disposition: string,
     *         effectiveDirective: string,
     *         originalPoliy: string,
     *         statusCode: int
     *     }
     * }|null> $payload
     */
    public function process(
        array $payload,
        ?string $userAgent = null,
        ?string $referrer = null,
    ): void {
        foreach ($this->createDtos($payload, $userAgent, $referrer) as $dto) {
            $this->processDto($dto);
        }

        $this->entityManager->flush();
    }

    /**
     * Creates the DTO(s) from the payload.
     *
     * @param array{csp-report: array{
     *     document-uri:string,
     *     referrer?: string,
     *     violated-directive: string,
     *     effective-directive: string,
     *     original-policy: string,
     *     blocked-uri: string,
     *     status-code: int,...
     * }}|list<array{
     *     age: int,
     *     type: string,
     *     url: string,
     *     user_agent: string,
     *     body: array{
     *         blockedUrl: string,
     *         disposition: string,
     *         effectiveDirective: string,
     *         originalPoliy: string,
     *         statusCode: int
     *     }
     * }|null> $payload
     *
     * @return iterable<CspReportDto>
     */
    private function createDtos(
        array $payload,
        ?string $userAgent,
        ?string $referrer,
    ): iterable {
        /*
         * Legacy format:
         *
         * {
         *   "csp-report": { ... }
         * }
         */
        if (isset($payload['csp-report'])) {
            /** @var array<string, mixed> $legacyReport */
            $legacyReport = $payload['csp-report'];

            /** @phpstan-ignore argument.type */
            yield $this->dtoFactory->fromLegacyReport($legacyReport, $userAgent, $referrer);

            return;
        }

        /*
         * Reporting API:
         *
         * [
         *   {
         *     "type": "csp-violation",
         *     ...
         *   }
         * ]
         */
        if (array_is_list($payload)) {
            foreach ($payload as $report) {
                if (
                    !is_array($report)
                    || ($report['type'] ?? null) !== 'csp-violation'
                ) {
                    continue;
                }

                /** @var array<string, mixed> $reportingApiReport */
                $reportingApiReport = $report;

                /** @phpstan-ignore argument.type */
                yield $this->dtoFactory->fromReportingApi($reportingApiReport, $userAgent, $referrer);
            }
        }
    }

    /**
     * Processes the DTO, ignoring if considered 'noise'.
     */
    private function processDto(CspReportDto $dto): void
    {
        if ($this->noiseFilter->isNoise($dto)) {
            return;
        }
        $fingerprint = $dto->fingerprint();

        $existing = $this->repository->findOneByFingerprint($fingerprint);
        if ($existing instanceof CspReport) {
            $this->updateExistingReport($existing);

            return;
        }

        $report = $this->createEntity($dto);
        $this->entityManager->persist($report);
    }

    /**
     * Updates an existing CSP report.
     */
    private function updateExistingReport(
        CspReport $report,
    ): void {
        $report->setOccurrences(
            $report->getOccurrences() + 1,
        );

        $report->setLastSeenAt(
            new \DateTimeImmutable(),
        );
    }

    /**
     * Create entity from DTO model.
     */
    private function createEntity(
        CspReportDto $dto,
    ): CspReport {
        $report = new CspReport();

        $report->setFingerprint(
            $dto->fingerprint(),
        );

        $report->setDocumentUri(
            $dto->documentUri,
        );

        $report->setBlockedUri(
            $dto->blockedUri,
        );

        $report->setHost(
            $dto->blockedHost(),
        );

        $report->setEffectiveDirective(
            $dto->effectiveDirective,
        );

        $report->setViolatedDirective(
            $dto->violatedDirective,
        );

        $report->setDisposition(
            $dto->disposition,
        );

        $report->setStatusCode(
            $dto->statusCode,
        );

        $report->setOriginalPolicy(
            $dto->originalPolicy,
        );

        $report->setSourceFile(
            $dto->sourceFile,
        );

        $report->setLineNumber(
            $dto->lineNumber,
        );

        $report->setColumnNumber(
            $dto->columnNumber,
        );

        $report->setReferrer(
            $dto->referrer,
        );

        $report->setUserAgent(
            $dto->userAgent,
        );

        $report->setSeverity(
            $this->severityResolver->resolve($dto),
        );

        $report->setOccurrences(1);

        /** @var array<'csp-report'|int<0, max>, array{
         *     age: int,
         *     type: string,
         *     url: string,
         *     user_agent: string,
         *     body: array{
         *         blockedUrl: string,
         *         disposition: string,
         *         effectiveDirective: string,
         *         originalPoliy: string,
         *         statusCode: int
         *     }
         * }|array{
         *     document-uri: string,
         *     referrer?: string,
         *     violated-directive: string,
         *     effective-directive: string,
         *     original-policy: string,
         *     blocked-uri: string,
         *     status-code: int
         * }|null> $rawPayload */
        $rawPayload = $dto->rawPayload;

        $report->setPayload(
            $rawPayload,
        );

        $report->setFirstSeenAt(
            new \DateTimeImmutable(),
        );

        $report->setLastSeenAt(
            new \DateTimeImmutable(),
        );

        return $report;
    }
}
