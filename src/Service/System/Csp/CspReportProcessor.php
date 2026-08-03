<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Inachis\Entity\System\CspReport;
use Inachis\Model\System\CspReportDto;
use Inachis\Repository\System\CspReportRepository;
use Inachis\Service\System\Csp\CspReportDtoFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Processor for CSP reports
 */
final readonly class CspReportProcessor
{
    /**
     * Constructor for CspReportProcessor
     *
     * @param EntityManagerInterface $entityManager
     * @param CspReportRepository $repository
     * @param CspReportDtoFactory $dtoFactory
     * @param CspNoiseFilter $noiseFilter
     * @param CspSeverityResolver $severityResolver
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CspReportRepository $repository,
        private CspReportDtoFactory $dtoFactory,
        private CspNoiseFilter $noiseFilter,
        private CspSeverityResolver $severityResolver,
    ) {}

    /**
     * Creates DTOs from the supplied payload
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
     * @param string|null $userAgent
     * @param string|null $referrer
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
     * Creates the DTO(s) from the payload
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
     * @param string|null $userAgent
     * @param string|null $referrer
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
            yield $this->dtoFactory->fromLegacyReport(
                $payload['csp-report'],
                $userAgent,
                $referrer,
            );

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

                yield $this->dtoFactory->fromReportingApi(
                    $report,
                    $userAgent,
                    $referrer,
                );
            }
        }
    }

    /**
     * Processes the DTO, ignoring if considered 'noise'
     *
     * @param CspReportDto $dto
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
     * Updates an existing CSP report
     *
     * @param CspReport $report
     */
    private function updateExistingReport(
        CspReport $report,
    ): void {
        $report->setOccurrences(
            $report->getOccurrences() + 1
        );

        $report->setLastSeenAt(
            new \DateTimeImmutable()
        );
    }

    /**
     * Create entity from DTO model
     *
     * @param CspReportDto $dto
     * @return CspReport
     */
    private function createEntity(
        CspReportDto $dto,
    ): CspReport {
        $report = new CspReport();

        $report->setFingerprint(
            $dto->fingerprint()
        );

        $report->setDocumentUri(
            $dto->documentUri
        );

        $report->setBlockedUri(
            $dto->blockedUri
        );

        $report->setHost(
            $dto->blockedHost()
        );

        $report->setEffectiveDirective(
            $dto->effectiveDirective
        );

        $report->setViolatedDirective(
            $dto->violatedDirective
        );

        $report->setDisposition(
            $dto->disposition
        );

        $report->setStatusCode(
            $dto->statusCode
        );

        $report->setOriginalPolicy(
            $dto->originalPolicy
        );

        $report->setSourceFile(
            $dto->sourceFile
        );

        $report->setLineNumber(
            $dto->lineNumber
        );

        $report->setColumnNumber(
            $dto->columnNumber
        );

        $report->setReferrer(
            $dto->referrer
        );

        $report->setUserAgent(
            $dto->userAgent
        );

        $report->setSeverity(
            $this->severityResolver->resolve($dto)
        );

        $report->setOccurrences(1);

        $report->setPayload(
            $dto->rawPayload
        );

        $report->setFirstSeenAt(
            new \DateTimeImmutable()
        );

        $report->setLastSeenAt(
            new \DateTimeImmutable()
        );

        return $report;
    }
}
