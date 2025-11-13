<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Form\LoginType;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        return [
            new PreloadedExtension([new LoginType($translator)], [])
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
