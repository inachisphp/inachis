<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventListener;

use Inachis\Entity\User\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * Event listener for hashing passwords
 */
class HashPasswordListener implements EventSubscriber
{
    /**
     * @var UserPasswordEncoder
     */
    private UserPasswordEncoder $passwordEncoder;

    /**
     * HashPasswordListener constructor.
     * 
     * @param UserPasswordEncoder $passwordEncoder The password encoder
     */
    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Handles pre-persist events
     * 
     * @param LifecycleEventArgs $args The lifecycle event arguments
     */
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->encodePassword($entity);
        }
    }

    /**
     * Handles pre-update events
     * 
     * @param LifecycleEventArgs $args The lifecycle event arguments
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->encodePassword($entity);

            // necessary to force the update to see the change
            $em = $args->getEntityManager();
            $meta = $em->getClassMetadata(get_class($entity));
            $em->getUnitOfWork()->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }

    /**
     * Returns the events this listener is subscribed to
     * 
     * @return array<int, string> The events this listener is subscribed to
     */
    public function getSubscribedEvents(): array
    {
        return ['prePersist', 'preUpdate'];
    }

    /**
     * Encodes the password for the given user
     * 
     * @param User $entity The user
     */
    private function encodePassword(User $entity): void
    {
        if ($entity->getPlainPassword()) {
            $encoded = $this->passwordEncoder->encodePassword(
                $entity,
                $entity->getPlainPassword()
            );
            $entity->setPassword($encoded);
        }
    }
}
