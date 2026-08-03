<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Form\ForgotPasswordType;
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
