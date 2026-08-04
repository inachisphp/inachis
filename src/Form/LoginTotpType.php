<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds an amdin login form for TOTP entry.
 *
 * @extends AbstractType<array{
 *     code?: string,
 *     verify?: string,
 * }>
 */
class LoginTotpType extends AbstractType
{
    /**
     * Constructor for the LoginType.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Builds the login form.
     *
     * @param FormBuilderInterface<array{
     *     code?: string,
     *     verify?: string,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('code', TextType::class, [
               'attr' => [
                   'aria-required' => 'true',
                   'autocomplete' => 'one-time-code',
                   'class' => 'auth-code-field',
                   'inputmode' => 'numeric',
                   'maxlength' => 6,
                   'pattern' => '[0-9]{6}',
                   'placeholder' => '••••••',
                   'required' => true,
               ],
               'label' => 'Authentication code',
           ])
            ->add('trustDevice', CheckboxType::class, [
                'attr' => [
                    'class' => 'checkbox',
                ],
                'label' => 'Trust this device for 30 days',
                'required' => false,
            ])
            ->add('verify', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
            ]);
    }
}
