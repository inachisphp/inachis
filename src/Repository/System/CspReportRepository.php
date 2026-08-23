<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\System;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\System\CspReport;
use Inachis\Enum\System\CspSeverity;

/**
 * @extends ServiceEntityRepository<CspReport>
 */
class CspReportRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CspReport::class);
    }

    /**
     * Returns a specific report by the fingerprint.
     */
    public function findOneByFingerprint(
        string $fingerprint,
    ): ?CspReport {
        /** @var CspReport|null $result */
        $result = $this->createQueryBuilder('r')
            ->andWhere('r.fingerprint = :fingerprint')
            ->setParameter('fingerprint', $fingerprint)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    /**
     * Returns a count of today's reports.
     */
    public function countToday(): int
    {
        $start = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.lastSeenAt >= :start')
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of all critical reports.
     */
    public function countCritical(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.severity = :severity')
            ->setParameter('severity', CspSeverity::Critical)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of unique hosts.
     */
    public function countUniqueHosts(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.host)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of unique directives.
     */
    public function countUniqueDirectives(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.effectiveDirective)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the hosts with the most occurrences.
     *
     * @return list<array{host: string, occurrences: int}>
     */
    public function findTopHosts(int $limit = 10): array
    {
        /** @var list<array{host: string, occurrences: int}> $result */
        $result = $this->createQueryBuilder('r')
            ->select('r.host, SUM(r.occurrences) as occurrences')
            ->where('r.host IS NOT NULL')
            ->groupBy('r.host')
            ->orderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    /**
     * Returns the directives with the most occurrences.
     *
     * @return list<array{directive: string, occurrences: int}>
     */
    public function findTopDirectives(int $limit = 10): array
    {
        /** @var list<array{directive: string, occurrences: int}> $result */
        $result = $this->createQueryBuilder('r')
            ->select(
                'r.effectiveDirective as directive,
                SUM(r.occurrences) as occurrences',
            )
            ->groupBy('r.effectiveDirective')
            ->orderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    /**
     * Returns the top critical reports.
     *
     * @return list<array{
     *     host: string,
     *     documentUri: string,
     *     blockedUri: string,
     *     effectiveDirective: string,
     *     severity: string,
     *     occurrences: int,
     *     lastSeenAt: \DateTimeInterface
     * }>
     */
    public function findTopCritical(int $limit = 10): array
    {
        /** @var list<array{
         *     host: string,
         *     documentUri: string,
         *     blockedUri: string,
         *     effectiveDirective: string,
         *     severity: string,
         *     occurrences: int,
         *     lastSeenAt: \DateTimeInterface
         * }> $result */
        $result = $this->createQueryBuilder('r')
            ->select('
                r.id,
                r.host,
                r.documentUri,
                r.blockedUri,
                r.effectiveDirective,
                r.severity,
                r.occurrences,
                r.lastSeenAt
            ')
            ->andWhere('r.severity = :severity')
            ->setParameter('severity', CspSeverity::Critical)
            ->orderBy('r.occurrences', 'DESC')
            ->addOrderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    /**
     * Returns the top critical reports grouped by fingerprint.
     *
     * @return list<array{
     *     fingerprint: string,
     *     host: string,
     *     documentUri: string,
     *     blockedUri: string,
     *     effectiveDirective: string,
     *     severity: string,
     *     occurrences: int,
     *     lastSeenAt: \DateTimeInterface
     * }>
     */
    public function findTopCriticalGrouped(int $limit = 10): array
    {
        /** @var list<array{
         *     fingerprint: string,
         *     host: string,
         *     documentUri: string,
         *     blockedUri: string,
         *     effectiveDirective: string,
         *     severity: string,
         *     occurrences: int,
         *     lastSeenAt: \DateTimeInterface
         * }> $result */
        $result = $this->createQueryBuilder('r')
            ->select('
                r.fingerprint,
                r.host,
                r.documentUri,
                r.blockedUri,
                r.effectiveDirective,
                r.severity,
                SUM(r.occurrences) AS occurrences,
                MAX(r.lastSeenAt) AS lastSeenAt
            ')
            ->andWhere('r.severity = :severity')
            ->setParameter('severity', CspSeverity::Critical)
            ->groupBy('
                r.fingerprint,
                r.host,
                r.documentUri,
                r.blockedUri,
                r.effectiveDirective,
                r.severity
            ')
            ->orderBy('lastSeenAt', 'DESC')
            ->addOrderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return $result;
    }

    /**
     * Returns the recent CSP Reports.
     *
     * @return list<CspReport>
     */
    public function findRecent(int $limit = 100): array
    {
        /** @var list<CspReport> $result */
        $result = $this->createQueryBuilder('r')
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Returns filtered CSP reports.
     *
     * @return list<array<string, string>>
     */
    public function findFiltered(
        ?string $severity,
        ?string $host,
        ?string $directive,
        ?bool $includeProcessed,
    ): array {
        $qb = $this->createQueryBuilder('r');

        if ($severity) {
            $qb->andWhere('r.severity = :severity')
                ->setParameter('severity', $severity);
        }

        if ($host) {
            $qb->andWhere('r.host = :host')
                ->setParameter('host', $host);
        }

        if ($directive) {
            $qb->andWhere('r.effectiveDirective = :directive')
                ->setParameter('directive', $directive);
        }

        if (!$includeProcessed) {
            $qb->andWhere('r.processed = :processed')
                ->setParameter('processed', false);
        }

        /** @var list<array<string, string>> $result */
        $result = $qb
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Returns a list of blocked URIs per directive.
     *
     * @return list<array{effectiveDirective: string, blockedUri: string}>
     */
    public function findUniqueDirectivesAndBlockedUris(): array
    {
        /** @var list<array{effectiveDirective: string, blockedUri: string}> $result */
        $result = $this->createQueryBuilder('r')
            ->select('DISTINCT r.effectiveDirective, r.blockedUri')
            ->where('r.effectiveDirective IS NOT NULL')
            ->andWhere('r.blockedUri IS NOT NULL')
            ->getQuery()
            ->getScalarResult();

        return $result;
    }

    /**
     * Mark similar reports as processed.
     */
    public function processSimilarReports(string $directive, string $blockedUri): int
    {
        $processedCount = 0;
        $similarReports = $this->findBy([
            'violatedDirective' => $directive,
            'processed' => 0,
        ]);

        $cleanSource = $blockedUri;
        if (filter_var($blockedUri, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($blockedUri);
            $cleanSource = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
        }

        foreach ($similarReports as $item) {
            $itemUri = $item->getBlockedUri();
            $itemSource = $itemUri;

            if (filter_var($itemUri, FILTER_VALIDATE_URL)) {
                $parsedItem = parse_url($itemUri ?? '');
                $itemSource = ($parsedItem['scheme'] ?? 'https').'://'.($parsedItem['host'] ?? '');
            }
            if ($itemSource === $cleanSource) {
                $item->setProcessed(true);
                ++$processedCount;
            }
        }

        return $processedCount;
    }

    /**
     * Deletes reports older than the specified date.
     *
     * @return int The number of records deleted
     */
    public function deleteOldReports(\DateTimeInterface $date): int
    {
        $result = $this->createQueryBuilder('r')
            ->delete()
            ->where('r.updatedAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();

        return is_numeric($result) ? (int) $result : 0;
    }
}
