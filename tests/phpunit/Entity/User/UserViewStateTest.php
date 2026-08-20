<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\User;
use Inachis\Entity\User\UserViewState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserViewStateTest extends TestCase
{
    #[Test]
    public function itInstantiatesUserViewStateWithDefaultValues(): void
    {
        $user = $this->createMock(User::class);
        $context = 'post';

        $viewState = new UserViewState($user, $context);

        self::assertSame($user, $viewState->getUser());
        self::assertSame('post', $viewState->getContext());
        self::assertSame([], $viewState->getState());
    }

    #[Test]
    public function itSetsAndGetsState(): void
    {
        $user = $this->createMock(User::class);
        $viewState = new UserViewState($user, 'page');

        $stateData = [
            'columns' => ['title', 'date', 'status'],
            'sort' => 'date',
            'order' => 'DESC',
            'itemsPerPage' => 25,
        ];

        self::assertSame($viewState, $viewState->setState($stateData));
        self::assertSame($stateData, $viewState->getState());
    }
}
