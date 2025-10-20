<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Entity\Image;
use App\Entity\User;
use App\Form\UserType;
use App\Util\RandomColorPicker;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserTypeTest extends TypeTestCase
{
    protected UuidInterface $uuid;

    protected function getExtensions(): array
    {
        $this->uuid = Uuid::uuid1();
        $translator = $this->createMock(TranslatorInterface::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn((new User())->setId($this->uuid));
        return [new PreloadedExtension([new UserType($translator, $security)], [])];
    }

    public function testConfigureOptionsSetsDataClass(): void
    {
        $form = $this->factory->create(UserType::class, new User());
        $options = $form->getConfig()->getOptions();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertSame(User::class, $options['data_class']);
    }

    public function testBuildFormForNewUser(): void
    {
        $user = new User();
        $form = $this->factory->create(UserType::class, $user);
        $view = $form->createView();

        $expectedFields = ['username', 'displayName', 'email', 'timezone', 'avatar', 'submit'];
        $this->assertSame($expectedFields, array_keys($view->children));

        $usernameType = $form->get('username')->getConfig()->getType()->getInnerType()::class;
        $this->assertSame('Symfony\Component\Form\Extension\Core\Type\TextType', $usernameType);

        $emailAttr = $form->get('email')->getConfig()->getOption('attr');
        $this->assertFalse($emailAttr['readOnly']);
    }

    public function testBuildFormForExistingUser(): void
    {
        $user = (new User('existing user'))->setId(Uuid::uuid1());
        $form = $this->factory->create(UserType::class, $user);
        $view = $form->createView();

        $this->assertContains('color', array_keys($view->children));

        $usernameType = $form->get('username')->getConfig()->getType()->getInnerType()::class;
        $this->assertSame('Symfony\Component\Form\Extension\Core\Type\HiddenType', $usernameType);

        $emailAttr = $form->get('email')->getConfig()->getOption('attr');
        $this->assertTrue($emailAttr['readOnly']);
    }

    public function testBuildFormForCurrentUser(): void
    {
        $user = (new User('existing user'))->setId($this->uuid);
        $form = $this->factory->create(UserType::class, $user);
        $view = $form->createView();

        $this->assertContains('color', array_keys($view->children));

        $usernameType = $form->get('username')->getConfig()->getType()->getInnerType()::class;
        $this->assertSame('Symfony\Component\Form\Extension\Core\Type\HiddenType', $usernameType);

        $emailAttr = $form->get('email')->getConfig()->getOption('attr');
        $this->assertTrue($emailAttr['readOnly']);
    }

    public function testColorFieldChoicesAndAttributes(): void
    {
        $user = new User('existing user');
        $user->setId(Uuid::uuid1());
        $form = $this->factory->create(UserType::class, $user);
        $this->assertTrue($form->has('color'), 'Color field should exist for existing users.');
        $colorField = $form->get('color');
        $choices = $colorField->getConfig()->getOption('choices');
        $expectedColors = RandomColorPicker::getAll();
        $this->assertSame(array_combine($expectedColors, $expectedColors), $choices);
        $choiceAttr = $colorField->getConfig()->getOption('choice_attr');
        $this->assertIsCallable($choiceAttr);
        $sample = $expectedColors[0];
        $this->assertSame(['data-color' => $sample], $choiceAttr($sample, $sample, $sample));
    }

    public function testTimezoneFieldContainsKnownChoices(): void
    {
        $user = new User();
        $user->setUsername('');

        $form = $this->factory->create(UserType::class, $user);
        $choices = $form->get('timezone')->getConfig()->getOption('choices');

        $this->assertNotEmpty($choices);
        $this->assertArrayHasKey('UTC', $choices);
        $this->assertSame('UTC', $choices['UTC']);
    }

    public function testFormSubmissionPopulatesEntityCorrectly(): void
    {
        $user = new User();
        $user->setUsername('');

        $formData = [
            'username' => 'new_user',
            'displayName' => 'John Doe',
            'email' => 'john@example.com',
            'timezone' => 'UTC',
            'avatar' => new Image(),
        ];

        $form = $this->factory->create(UserType::class, $user);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('new_user', $user->getUsername());
        $this->assertSame('John Doe', $user->getDisplayName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('UTC', $user->getTimezone());
    }
}
