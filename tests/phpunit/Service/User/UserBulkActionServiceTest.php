<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\User;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Repository\User\UserRepository;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserProtectionServiceInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UserBulkActionServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private User $user;
    private UserRepository $userRepository;
    private UserBulkActionService $userBulkActionService;
    private UserProtectionServiceInterface $userProtectionService;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->user = (new User())->setId(Uuid::uuid4());
        $this->user->setUsername('test-user');
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->userRepository
            ->method('find')
            ->willReturn($this->user);
        $this->entityManager = $this->createStub(EntityManager::class);
        $this->userProtectionService = $this->createMock(UserProtectionServiceInterface::class);

        $this->userBulkActionService = new UserBulkActionService(
            $this->userProtectionService,
            $this->userRepository,
            $this->entityManager,
        );
    }

    /**
     * @throws Exception
     */
    public function testApplyUserNotFound(): void
    {
        $uuid = Uuid::uuid1()->toString();
        $this->user->setUsername('');
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($uuid)
            ->willReturn($this->user);
        $this->userBulkActionService = new UserBulkActionService(
            $this->userProtectionService,
            $this->userRepository,
            $this->entityManager,
        );

        $result = $this->userBulkActionService->apply('', [$uuid]);
        $this->assertEquals(0, $result);
    }

    public function testApplyDelete(): void
    {
        $this->userProtectionService
            ->expects($this->once())
            ->method('assertAdministratorsCanBeRemoved')
            ->with([$this->user]);
        $result = $this->userBulkActionService->apply('delete', [$this->user->getId()]);
        $this->assertEquals(1, $result);
        $this->assertTrue($this->user->hasBeenRemoved());
    }

    public function testApplyEnable(): void
    {
        $result = $this->userBulkActionService->apply('enable', [$this->user->getId()]);
        $this->userProtectionService
            ->expects($this->never())
            ->method('assertAdministratorsCanBeRemoved');
        $this->assertEquals(1, $result);
        $this->assertTrue($this->user->isEnabled());
    }

    public function testApplyDisable(): void
    {
        $this->userProtectionService
            ->expects($this->once())
            ->method('assertAdministratorsCanBeRemoved')
            ->with([$this->user]);
        $result = $this->userBulkActionService->apply('disable', [$this->user->getId()]);
        $this->assertEquals(1, $result);
        $this->assertFalse($this->user->isEnabled());
    }

    public function testApplyDefault(): void
    {
        $result = $this->userBulkActionService->apply('', [$this->user->getId()]);
        $this->userProtectionService
            ->expects($this->never())
            ->method('assertAdministratorsCanBeRemoved');
        $this->assertEquals(1, $result);
    }
}
