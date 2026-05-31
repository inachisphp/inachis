<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EntityListener;

use Inachis\Entity\Setting;
use Inachis\Service\EncryptionService;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class SettingListener
{
    /**
     * Construct the listener with the encryption service
     *
     * @param EncryptionService $crypto
     */
    public function __construct(
        private readonly EncryptionService $crypto
    ) {
    }

    /**
     * Encrypt the setting's value before saving to the database
     *
     * @param Setting $setting
     * @param PrePersistEventArgs $event
     * @return void
     */
    public function prePersist(
        Setting $setting,
        PrePersistEventArgs $event
    ): void {
        $this->encryptSetting($setting);
    }

    /**
     * Encrypt the setting's value before updating
     *
     * @param Setting $setting
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(
        Setting $setting,
        PreUpdateEventArgs $event
    ): void {
        $this->encryptSetting($setting);

        $em = $event->getObjectManager();
        $uow = $em->getUnitOfWork();

        $uow->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(Setting::class),
            $setting
        );
    }

    /**
     * Decrypt the setting's value after loading from the database
     *
     * @param Setting $setting
     * @param PostLoadEventArgs $event
     */
    public function postLoad(
        Setting $setting,
        PostLoadEventArgs $event
    ): void {
        if (
            empty($setting->getEncryptedValue()) ||
            empty($setting->getEncryptedKey())
        ) {
            return;
        }

        $rowKey = $this->crypto->unwrapKey(
            $setting->getEncryptedKey()
        );

        $setting->setValue(
            $this->crypto->decryptValue(
                $setting->getEncryptedValue(),
                $rowKey
            )
        );
    }

    /**
     * Encrypt the setting's value
     *
     * @param Setting $setting
     */
    private function encryptSetting(
        Setting $setting
    ): void {
        $value = $setting->getValue();

        if ($value === null) {
            return;
        }

        /**
         * Generate a fresh row key each time
         * so updated settings are automatically
         * re-keyed.
         */
        $rowKey = $this->crypto->generateRowKey();

        $setting->setEncryptedKey(
            $this->crypto->wrapKey($rowKey)
        );

        $setting->setEncryptedValue(
            $this->crypto->encryptValue(
                $value,
                $rowKey
            )
        );
    }
}
