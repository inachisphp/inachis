<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Form\Extension\TogglePasswordTypeExtension;
use Inachis\Form\LoginType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class LoginTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        return [
            new PreloadedExtension([
                new LoginType($translator)
            ], [
                PasswordType::class => [
                    new TogglePasswordTypeExtension(),
                ],
            ])
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(LoginType::class, []);
        $view = $form->createView();

        $expectedFields = [ 'loginUsername', 'loginPassword', 'logIn', ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
