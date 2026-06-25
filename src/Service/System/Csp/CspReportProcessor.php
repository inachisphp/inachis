<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Csp;

use Inachis\Entity\System\CspReport;
use Inachis\Model\System\CspReportDto;
use Inachis\Repository\System\CspReportRepository;
use Inachis\Service\System\Csp\CspReportDtoFactory;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CspReportProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CspReportRepository $repository,
        private CspReportDtoFactory $dtoFactory,
        private CspNoiseFilter $noiseFilter,
        private CspSeverityResolver $severityResolver,
    ) {
    }

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
