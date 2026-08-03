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
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CspReport::class);
    }

    /**
     * Returns a specific report by the fingerprint
     *
     * @param string $fingerprint
     * @return CspReport|null
     */
    public function findOneByFingerprint(
        string $fingerprint,
    ): ?CspReport {
        /** @var CspReport|null */
        return $this->createQueryBuilder('r')
            ->andWhere('r.fingerprint = :fingerprint')
            ->setParameter('fingerprint', $fingerprint)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns a count of today's reports
     *
     * @return int
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
     * Returns a count of all critical reports
     *
     * @return int
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
     * Returns a count of unique hosts
     *
     * @return int
     */
    public function countUniqueHosts(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.host)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns a count of unique directives
     *
     * @return int
     */
    public function countUniqueDirectives(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.effectiveDirective)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the hosts with the most occurrences
     *
     * @param int $limit
     * @return list<array{host: string, occurrences: int}>
     */
    public function findTopHosts(int $limit = 10): array
    {
        /** @var list<array{host: string, occurrences: int}> */
        return $this->createQueryBuilder('r')
            ->select('r.host, SUM(r.occurrences) as occurrences')
            ->where('r.host IS NOT NULL')
            ->groupBy('r.host')
            ->orderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns the directives with the most occurrences
     *
     * @param int $limit
     * @return list<array{directive: string, occurrences: int}>
     */
    public function findTopDirectives(int $limit = 10): array
    {
        /** @var list<array{directive: string, occurrences: int}> */
        return $this->createQueryBuilder('r')
            ->select(
                'r.effectiveDirective as directive,
                SUM(r.occurrences) as occurrences'
            )
            ->groupBy('r.effectiveDirective')
            ->orderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Returns the top critical reports
     *
     * @param int $limit
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
        /** 
         * @var list<array{
         *     host: string,
         *     documentUri: string,
         *     blockedUri: string,
         *     effectiveDirective: string,
         *     severity: string,
         *     occurrences: int,
         *     lastSeenAt: \DateTimeInterface
         * }>
         */
        return $this->createQueryBuilder('r')
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
    }

    /**
     * Returns the top critical reports grouped by fingerprint
     *
     * @param int $limit
     * @return list<array{
     *    fingerprint: string,
     *     host: string,
     *     documentUri: string,
     *     blockedUri: string,
     *     effectiveDirective: string,
     *     severity: string,
     *     occurrences: int,
     *     lastSeenAt: \DateTimeInterface
     *  }>
     */
    public function findTopCriticalGrouped(int $limit = 10): array
    {
        /** @var list<array{
         *    fingerprint: string,
         *     host: string,
         *     documentUri: string,
         *     blockedUri: string,
         *     effectiveDirective: string,
         *     severity: string,
         *     occurrences: int,
         *     lastSeenAt: \DateTimeInterface
         *  }>
         */
        return $this->createQueryBuilder('r')
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
    }

    /**
     * Returns the recent CSP Reports
     *
     * @param int $limit
     * @return list<CspReport>
     */
    public function findRecent(int $limit = 100): array
    {
        /** @var list<CspReport> */
        return $this->createQueryBuilder('r')
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns filtered CSP reports
     *
     * @param string|null $severity
     * @param string|null $host
     * @param string|null $directive
     * @param bool|null $includeProcessed
     * @return list<array<string,string>>
     */
    public function findFiltered(
        ?string $severity,
        ?string $host,
        ?string $directive,
        ?bool $includeProcessed
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

        /** @var list<array<string,string>> */
        return $qb
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a list of blocked URIs per directive
     *
     * @return list<array{effectiveDirective: string, blockedUri: string}>
     */
    public function findUniqueDirectivesAndBlockedUris(): array
    {
        /** @var list<array{effectiveDirective: string, blockedUri: string}> */
        return $this->createQueryBuilder('r')
            ->select('DISTINCT r.effectiveDirective, r.blockedUri')
            ->where('r.effectiveDirective IS NOT NULL')
            ->andWhere('r.blockedUri IS NOT NULL')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Mark similar reports as processed
     *
     * @param string $directive
     * @param string $blockedUri
     * @return int
     */
    public function processSimilarReports(string $directive, string $blockedUri): int
    {
        $processedCount = 0;
        $similarReports = $this->findBy([
            'violatedDirective' => $directive,
            'processed' => 0
        ]);

        $cleanSource = $blockedUri;
        if (filter_var($blockedUri, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($blockedUri);
            $cleanSource = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        }

        foreach ($similarReports as $item) {
            $itemUri = $item->getBlockedUri();
            $itemSource = $itemUri;
            
            if (filter_var($itemUri, FILTER_VALIDATE_URL)) {
                $parsedItem = parse_url($itemUri ?? '');
                $itemSource = ($parsedItem['scheme'] ?? 'https') . '://' . ($parsedItem['host'] ?? '');
            }
            if ($itemSource === $cleanSource) {
                $item->setProcessed(true);
                ++$processedCount;
            }
        }

        return $processedCount;
    }

    /**
     * Deletes reports older than the specified date
     *
     * @param \DateTimeInterface $date
     * @return int The number of records deleted
     */
    public function deleteOldReports(\DateTimeInterface $date): int
    {
        /** @var int */
       return $this->createQueryBuilder('r')
           ->delete()
           ->where('r.updatedAt < :date')
           ->setParameter('date', $date)
           ->getQuery()
           ->execute();
   }
}
