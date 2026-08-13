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
use Inachis\Entity\User\UserTotp;
use Inachis\EntityListener\UserTotpListener;
use Inachis\Service\Crypto\EncryptionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserTotpListenerTest extends TestCase
{
    private EncryptionService&MockObject $crypto;
    private UserTotpListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crypto = $this->createMock(EncryptionService::class);
        $this->listener = new UserTotpListener($this->crypto);
    }

    #[Test]
    public function itEncryptsSecretOnPrePersistWhenSecretIsPresent(): void
    {
        $totp = $this->createMock(UserTotp::class);
        $totp->method('getSecret')->willReturn('JBSWY3DPEHPK3PXP');

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
            ->with('JBSWY3DPEHPK3PXP', 'generated-row-key')
            ->willReturn('encrypted-secret-value');

        $totp->expects(self::once())
            ->method('setEncryptedKey')
            ->with('wrapped-key');

        $totp->expects(self::once())
            ->method('setEncryptedSecret')
            ->with('encrypted-secret-value');

        $event = new PrePersistEventArgs($totp, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($totp, $event);
    }

    #[Test]
    public function itDoesNotEncryptSecretOnPrePersistWhenSecretIsNull(): void
    {
        $totp = $this->createMock(UserTotp::class);
        $totp->method('getSecret')->willReturn(null);

        $this->crypto->expects(self::never())->method('generateRowKey');
        $this->crypto->expects(self::never())->method('wrapKey');
        $this->crypto->expects(self::never())->method('encryptValue');

        $event = new PrePersistEventArgs($totp, $this->createMock(EntityManagerInterface::class));

        $this->listener->prePersist($totp, $event);
    }

    #[Test]
    public function itDoesNotEncryptOrRecomputeOnPreUpdateWhenSecretHasNotChanged(): void
    {
        $totp = $this->createMock(UserTotp::class);

        $this->crypto->expects(self::never())->method('generateRowKey');
        $this->crypto->expects(self::never())->method('wrapKey');
        $this->crypto->expects(self::never())->method('encryptValue');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getUnitOfWork');

        $changeSet = ['otherField' => ['old', 'new']];
        $event = new PreUpdateEventArgs($totp, $entityManager, $changeSet);

        $this->listener->preUpdate($totp, $event);
    }

    #[Test]
    public function itEncryptsSecretAndRecomputesChangeSetOnPreUpdateWhenSecretHasChanged(): void
    {
        $totp = $this->createMock(UserTotp::class);
        $totp->method('getSecret')->willReturn('NEWSECRET1234567');

        $this->crypto
            ->expects(self::once())
            ->method('generateRowKey')
            ->willReturn('new-row-key');

        $this->crypto
            ->expects(self::once())
            ->method('wrapKey')
            ->with('new-row-key')
            ->willReturn('new-wrapped-key');

        $this->crypto
            ->expects(self::once())
            ->method('encryptValue')
            ->with('NEWSECRET1234567', 'new-row-key')
            ->willReturn('new-encrypted-secret');

        $totp->expects(self::once())
            ->method('setEncryptedKey')
            ->with('new-wrapped-key');

        $totp->expects(self::once())
            ->method('setEncryptedSecret')
            ->with('new-encrypted-secret');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $unitOfWork
            ->expects(self::once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($classMetadata, $totp);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with(UserTotp::class)
            ->willReturn($classMetadata);

        $changeSet = ['secret' => ['OLDSECRET', 'NEWSECRET1234567']];
        $event = new PreUpdateEventArgs($totp, $entityManager, $changeSet);

        $this->listener->preUpdate($totp, $event);
    }

    #[Test]
    public function itDoesNotDecryptOnPostLoadWhenHasSecretIsFalse(): void
    {
        $totp = $this->createMock(UserTotp::class);
        $totp->method('hasSecret')->willReturn(false);

        $this->crypto->expects(self::never())->method('unwrapKey');
        $this->crypto->expects(self::never())->method('decryptValue');

        $event = new PostLoadEventArgs($totp, $this->createMock(EntityManagerInterface::class));

        $this->listener->postLoad($totp, $event);
    }

    #[Test]
    public function itDecryptsSecretOnPostLoadWhenHasSecretIsTrue(): void
    {
        $totp = $this->createMock(UserTotp::class);
        $totp->method('hasSecret')->willReturn(true);
        $totp->method('getEncryptedKey')->willReturn('wrapped-key-123');
        $totp->method('getEncryptedSecret')->willReturn('encrypted-secret-123');

        $this->crypto
            ->expects(self::once())
            ->method('unwrapKey')
            ->with('wrapped-key-123')
            ->willReturn('unwrapped-row-key');

        $this->crypto
            ->expects(self::once())
            ->method('decryptValue')
            ->with('encrypted-secret-123', 'unwrapped-row-key')
            ->willReturn('DECRYPTEDSECRET321');

        $totp->expects(self::once())
            ->method('setSecret')
            ->with('DECRYPTEDSECRET321');

        $event = new PostLoadEventArgs($totp, $this->createMock(EntityManagerInterface::class));

        $this->listener->postLoad($totp, $event);
    }
}
