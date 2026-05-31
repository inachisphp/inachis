<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Setting;

class SettingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Setting::class);
    }

    public function getByName(string $name): ?Setting
    {
        return $this->findOneBy([
            'name' => $name,
        ]);
    }

    public function getValue(string $name): ?string
    {
        $setting = $this->getByName($name);

        return $setting?->getValue();
    }

    public function has(string $name): bool
    {
        return $this->getByName($name) !== null;
    }

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

        $em = $this->getEntityManager();

        $em->persist($setting);
        $em->flush();

        return $setting;
    }

    public function removeByName(string $name): void
    {
        $setting = $this->getByName($name);

        if (!$setting) {
            return;
        }

        $em = $this->getEntityManager();

        $em->remove($setting);
        $em->flush();
    }

    /**
     * Returns all settings indexed by name.
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
