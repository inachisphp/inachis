<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Inachis\Entity\System\Setting;
use Inachis\EntityListener\SettingListener;
use Inachis\Service\Crypto\EncryptionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SettingListenerTest extends TestCase
{
    private EncryptionService&MockObject $crypto;
    private SettingListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crypto = $this->createMock(EncryptionService::class);
        $this->listener = new SettingListener($this->crypto);
    }

    #[Test]
    public function itEncryptsSettingValueOnPrePersistWhenValueIsPresent(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getValue')->willReturn('my-secret-setting');

        $this->crypto
            ->expects(self::once())
            ->method('generateRowKey')
            ->willReturn('generated-row-key');

        $this->crypto
            ->expects(self::once())
            ->method('wrapKey')
            ->with('generated-row-key')
            ->willReturn('wrapped-key');

        $this->crypto
            ->expects(self::once())
            ->method('encryptValue')
            ->with('my-secret-setting', 'generated-row-key')
            ->willReturn('encrypted-setting-value');

        $setting->expects(self::once())
            ->method('setEncryptedKey')
            ->with('wrapped-key');

        $setting->expects(self::once())
            ->method('setEncryptedValue')
            ->with('encrypted-setting-value');

        $event = new PrePersistEventArgs($setting, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($setting, $event);
    }

    #[Test]
    public function itDoesNotEncryptSettingOnPrePersistWhenValueIsNull(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getValue')->willReturn(null);

        $this->crypto->expects(self::never())->method('generateRowKey');
        $this->crypto->expects(self::never())->method('wrapKey');
        $this->crypto->expects(self::never())->method('encryptValue');

        $event = new PrePersistEventArgs($setting, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($setting, $event);
    }

    #[Test]
    public function itEncryptsSettingAndRecomputesChangeSetOnPreUpdate(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getValue')->willReturn('updated-secret');

        $this->crypto
            ->expects(self::once())
            ->method('generateRowKey')
            ->willReturn('row-key-123');

        $this->crypto
            ->expects(self::once())
            ->method('wrapKey')
            ->with('row-key-123')
            ->willReturn('wrapped-key-123');

        $this->crypto
            ->expects(self::once())
            ->method('encryptValue')
            ->with('updated-secret', 'row-key-123')
            ->willReturn('encrypted-secret-123');

        $setting->expects(self::once())
            ->method('setEncryptedKey')
            ->with('wrapped-key-123');

        $setting->expects(self::once())
            ->method('setEncryptedValue')
            ->with('encrypted-secret-123');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $unitOfWork
            ->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($classMetadata, $setting);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with(Setting::class)
            ->willReturn($classMetadata);

        $changeSet = [];
        $event = new PreUpdateEventArgs($setting, $entityManager, $changeSet);

        $this->listener->preUpdate($setting, $event);
    }

    #[Test]
    public function itDoesNotDecryptOnPostLoadWhenEncryptedValueIsEmpty(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getEncryptedValue')->willReturn('');
        $setting->method('getEncryptedKey')->willReturn('some-key');

        $this->crypto->expects(self::never())->method('unwrapKey');
        $this->crypto->expects(self::never())->method('decryptValue');

        $event = new PostLoadEventArgs($setting, $this->createMock(EntityManagerInterface::class));

        $this->listener->postLoad($setting, $event);
    }

    #[Test]
    public function itDoesNotDecryptOnPostLoadWhenEncryptedKeyIsEmpty(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getEncryptedValue')->willReturn('some-encrypted-val');
        $setting->method('getEncryptedKey')->willReturn('');

        $this->crypto->expects(self::never())->method('unwrapKey');
        $this->crypto->expects(self::never())->method('decryptValue');

        $event = new PostLoadEventArgs($setting, $this->createMock(EntityManagerInterface::class));

        $this->listener->postLoad($setting, $event);
    }

    #[Test]
    public function itDecryptsSettingValueOnPostLoadWhenEncryptedDataIsPresent(): void
    {
        $setting = $this->createMock(Setting::class);
        $setting->method('getEncryptedKey')->willReturn('wrapped-key-456');
        $setting->method('getEncryptedValue')->willReturn('encrypted-val-456');

        $this->crypto
            ->expects(self::once())
            ->method('unwrapKey')
            ->with('wrapped-key-456')
            ->willReturn('raw-row-key');

        $this->crypto
            ->expects(self::once())
            ->method('decryptValue')
            ->with('encrypted-val-456', 'raw-row-key')
            ->willReturn('plain-text-value');

        $setting->expects(self::once())
            ->method('setValue')
            ->with('plain-text-value');

        $event = new PostLoadEventArgs($setting, $this->createMock(EntityManagerInterface::class));

        $this->listener->postLoad($setting, $event);
    }
}
