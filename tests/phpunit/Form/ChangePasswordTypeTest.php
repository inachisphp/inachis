<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Form\ChangePasswordType;
use Inachis\Form\Extension\TogglePasswordTypeExtension;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilderInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class ChangePasswordTypeTest extends TypeTestCase
{
    private FormFactoryBuilderInterface $formFactory;

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $validator  = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
            new PreloadedExtension(
                [
                    new ChangePasswordType($translator),
                ],
                [
                    PasswordType::class => [
                        new TogglePasswordTypeExtension(),
                    ],
                ]
            ),
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null);
        $view = $form->createView();

        $expectedFields = [ 'current_password', 'new_password', ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }

    public function testBuildFormForForgotChange(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [ 'password_reset' => true, ]);
        $view = $form->createView();

        $expectedFields = [ 'username', 'resetPassword', 'new_password', ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
