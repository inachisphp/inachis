<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Repository\Media;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Media\Audio;
use Inachis\Entity\Media\AudioVersion;

/**
 * Repository for AudioVersion entities.
 *
 * @extends ServiceEntityRepository<AudioVersion>
 */
class AudioVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AudioVersion::class);
    }

    /**
     * Returns all versions of an audio resource, newest first.
     *
     * @return list<AudioVersion>
     */
    public function findByAudio(Audio $audio): array
    {
        return $this->createQueryBuilder('version')
            ->andWhere('version.audio = :audio')
            ->setParameter('audio', $audio)
            ->orderBy('version.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds a specific version of an audio resource.
     */
    public function findVersion(
        Audio $audio,
        int $versionNumber,
    ): ?AudioVersion {
        return $this->createQueryBuilder('version')
            ->andWhere('version.audio = :audio')
            ->andWhere('version.versionNumber = :versionNumber')
            ->setParameter('audio', $audio)
            ->setParameter('versionNumber', $versionNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds a specific version or throws an exception.
     */
    public function findVersionOrFail(
        Audio $audio,
        int $versionNumber,
    ): AudioVersion {
        $version = $this->findVersion($audio, $versionNumber);

        if (null === $version) {
            throw new \RuntimeException(
                sprintf(
                    'Version %d of audio resource "%s" could not be found.',
                    $versionNumber,
                    $audio->getId()?->toString() ?? 'unknown',
                ),
            );
        }

        return $version;
    }

    /**
     * Returns the latest version of an audio resource.
     */
    public function findLatestVersion(Audio $audio): ?AudioVersion
    {
        return $this->createQueryBuilder('version')
            ->andWhere('version.audio = :audio')
            ->setParameter('audio', $audio)
            ->orderBy('version.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the number of versions belonging to an audio resource.
     */
    public function countByAudio(Audio $audio): int
    {
        return (int) $this->createQueryBuilder('version')
            ->select('COUNT(version.id)')
            ->andWhere('version.audio = :audio')
            ->setParameter('audio', $audio)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns the next available version number for an audio resource.
     */
    public function getNextVersionNumber(Audio $audio): int
    {
        $maximum = $this->createQueryBuilder('version')
            ->select('MAX(version.versionNumber)')
            ->andWhere('version.audio = :audio')
            ->setParameter('audio', $audio)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $maximum ? 1 : ((int) $maximum + 1);
    }
}
