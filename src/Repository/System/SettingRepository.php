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
use Inachis\Entity\System\Setting;

/**
  * @extends ServiceEntityRepository<Setting>
 */
class SettingRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Returns a setting by name
     *
     * @param string $name
     * @return Setting|null
     */
    public function getByName(string $name): ?Setting
    {
        return $this->findOneBy([
            'name' => $name,
        ]);
    }

    /**
     * Returns a value for a setting based on its name
     *
     * @param string $name
     * @return string|null
     */
    public function getValue(string $name): ?string
    {
        $setting = $this->getByName($name);

        return $setting?->getValue();
    }

    /**
     * Returns result of checking if this setting exists by name
     *
     * @param string $name
     * @return boolean
     */
    public function has(string $name): bool
    {
        return $this->getByName($name) !== null;
    }

    /**
     * Sets the value for a named setting
     *
     * @param string $name
     * @param string|null $value
     * @return Setting
     */
    public function setValue(
        string $name,
        ?string $value
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
     * Removes a setting by name
     *
     * @param string $name
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
}
