<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds an amdin login form for TOTP entry
 * 
 * @extends AbstractType<array{
 *     code?: string,
 *     verify?: string,
 * }>
 */
class LoginTotpType extends AbstractType
{
    /**
     * Constructor for the LoginType
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator) {}

    /**
     * Builds the login form
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
                'label' => 'Authentication code',
                'attr' => [
                    'autocomplete' => 'one-time-code',
                    'inputmode' => 'numeric',
                    'maxlength' => 6,
                    'pattern' => '[0-9]{6}',
                    'required' => true,
                ],
            ])
            ->add('verify', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
            ]);
    }
}
