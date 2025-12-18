<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Form\ForgotPasswordType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class ForgotPasswordTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        return [
            new PreloadedExtension([new ForgotPasswordType($translator)], [])
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(ForgotPasswordType::class, []);
        $view = $form->createView();

        $expectedFields = [ 'forgot_email', 'resetPassword', ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
