<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Repository\Media;

use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Media\Audio;
use Inachis\Repository\AbstractRepository;
use Ramsey\Uuid\UuidInterface;

/**
 * Repository for Audio entities.
 * 
 * @extends AbstractRepository<Audio>
 *
 * @implements ResourceRepositoryInterface<Audio>
 */
class AudioRepository extends AbstractRepository implements ResourceRepositoryInterface
{
    /** @use DefaultResourceRepository<Audio> */
    use DefaultResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Audio::class);
    }

    /**
     * Finds an audio resource by its UUID.
     */
    public function findById(UuidInterface $id): ?Audio
    {
        return $this->find($id);
    }

    /**
     * Finds an audio resource by its UUID or throws an exception.
     */
    public function findByIdOrFail(UuidInterface $id): Audio
    {
        $audio = $this->find($id);

        if (null === $audio) {
            throw new \RuntimeException(
                sprintf(
                    'Audio resource "%s" could not be found.',
                    $id->toString(),
                ),
            );
        }

        return $audio;
    }
}
