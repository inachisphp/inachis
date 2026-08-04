<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\System;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\System\Setting;

/**
 * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Returns a setting by name.
     */
    public function getByName(string $name): ?Setting
    {
        return $this->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * Returns a value for a setting based on its name.
     */
    public function getValue(string $name): ?string
    {
        $setting = $this->getByName($name);

        return $setting?->getValue();
    }

    /**
     * Returns result of checking if this setting exists by name.
     */
    public function has(string $name): bool
    {
        return null !== $this->getByName($name);
    }

    /**
     * Sets the value for a named setting.
     */
    public function setValue(
        string $name,
        ?string $value,
    ): Setting {
        $setting = $this->getByName($name);

        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
        }

        $setting->setValue($value);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($setting);
        $entityManager->flush();

        return $setting;
    }

    /**
     * Removes a setting by name.
     */
    public function removeByName(string $name): void
    {
        $setting = $this->getByName($name);

        if (!$setting) {
            return;
        }

        $entityManager = $this->getEntityManager();
        $entityManager->remove($setting);
        $entityManager->flush();
    }

    /**
     * Returns all settings indexed by name.
     *
     * @return array<string,string|null>
     */
    public function getAllValues(): array
    {
        $result = [];

        foreach ($this->findAll() as $setting) {
            $result[$setting->getName()] = $setting->getValue();
        }

        return $result;
    }

    /**
     * Returns the specified setting, or creates it - does not flush.
     */
    public function getOrCreateSetting(string $name, string $default): Setting
    {
        $setting = $this->findOneBy(['name' => $name]);
        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
            $setting->setValue($default);
            $this->getEntityManager()->persist($setting);
        }

        return $setting;
    }
}
