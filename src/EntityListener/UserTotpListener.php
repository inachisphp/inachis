<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Inachis\Entity\User\UserTotp;
use Inachis\Service\Crypto\EncryptionService;

/**
 * Encrypts and decrypts UserTotp secrets.
 *
 * Uses envelope encryption:
 *
 * UserTotp secret
 *      |
 *      | encrypted with row key
 *      v
 * encryptedSecret
 *
 * Row key
 *      |
 *      | encrypted with master key
 *      v
 * encryptedKey
 */
#[AsEntityListener(
    event: Events::prePersist,
    method: 'prePersist',
    entity: UserTotp::class
)]
#[AsEntityListener(
    event: Events::preUpdate,
    method: 'preUpdate',
    entity: UserTotp::class
)]
#[AsEntityListener(
    event: Events::postLoad,
    method: 'postLoad',
    entity: UserTotp::class
)]
class UserTotpListener
{
    public function __construct(
        private readonly EncryptionService $crypto
    ) {}


    /**
     * Encrypt the TOTP secret before inserting.
     */
    public function prePersist(
        UserTotp $totp,
        PrePersistEventArgs $event
    ): void {
        $this->encryptTotpSecret($totp);
    }


    /**
     * Encrypt the TOTP secret before updating.
     */
    public function preUpdate(
        UserTotp $totp,
        PreUpdateEventArgs $event
    ): void {
        if (!$event->hasChangedField('secret')) {
            return;
        }
        $this->encryptTotpSecret($totp);

        /**
         * Encryption changes the persisted fields,
         * so Doctrine needs the updated change set.
         */
        $em = $event->getObjectManager();

        $uow = $em->getUnitOfWork();

        $uow->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(UserTotp::class),
            $totp
        );
    }


    /**
     * Decrypt the TOTP secret after loading.
     */
    public function postLoad(
        UserTotp $totp,
        PostLoadEventArgs $event
    ): void {
        if (!$totp->hasSecret()) {
            return;
        }


        $rowKey = $this->crypto->unwrapKey(
            $totp->getEncryptedKey()
        );


        $totp->setSecret(
            $this->crypto->decryptValue(
                $totp->getEncryptedSecret(),
                $rowKey
            )
        );
    }


    /**
     * Encrypt the current secret.
     */
    private function encryptTotpSecret(
        UserTotp $totp
    ): void {
        $secret = $totp->getSecret();


        /**
         * Nothing to encrypt.
         *
         * This can happen when Doctrine hydrates an existing
         * entity and only encrypted fields are present.
         */
        if ($secret === null) {
            return;
        }


        /**
         * Generate a fresh row key every time.
         *
         * This means updates automatically rotate
         * the encryption key protecting the secret.
         */
        $rowKey = $this->crypto->generateRowKey();


        $totp->setEncryptedKey(
            $this->crypto->wrapKey($rowKey)
        );


        $totp->setEncryptedSecret(
            $this->crypto->encryptValue(
                $secret,
                $rowKey
            )
        );
    }
}