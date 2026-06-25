<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
        return $this->createQueryBuilder('r')
            ->andWhere('r.fingerprint = :fingerprint')
            ->setParameter('fingerprint', $fingerprint)
            ->getQuery()
            ->getOneOrNullResult();
    }

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

    public function countCritical(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.severity = :severity')
            ->setParameter('severity', CspSeverity::Critical)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUniqueHosts(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.host)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUniqueDirectives(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.effectiveDirective)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findTopHosts(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.host, SUM(r.occurrences) as occurrences')
            ->where('r.host IS NOT NULL')
            ->groupBy('r.host')
            ->orderBy('occurrences', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findTopDirectives(int $limit = 10): array
    {
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

    public function findTopCritical(int $limit = 10): array
    {
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

    public function findTopCriticalGrouped(int $limit = 10): array
    {
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

    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findFiltered(?string $severity, ?string $host): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($severity) {
            $qb->andWhere('r.severity = :severity')
            ->setParameter('severity', $severity);
        }

        if ($host) {
            $qb->andWhere('r.host = :host')
            ->setParameter('host', $host);
        }

        return $qb
            ->orderBy('r.lastSeenAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }
}
