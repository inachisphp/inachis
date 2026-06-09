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
 * Undocumented class
 * 
 * @extends AbstractType<array{
 *     forgot_email?: string,
 * }>
 */
class ForgotPasswordType extends AbstractType
{
    /**
     * Constructor for the ForgotPasswordType
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator) {}

    /**
     * Builds the form
     *
     * @param FormBuilderInterface<array{
     *     forgot_email?: string,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('forgot_email', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'form-login__username-label',
                    'aria-required'   => 'true',
                    'autofocus'       => 'true',
                    'class'           => 'text',
                    'id'              => 'form-forgot__email',
                    'placeholder'     => $this->translator->trans('admin.email_example'),
                ],
                'label'      => $this->translator->trans('admin.reset.email_address.label'),
                'label_attr' => [
                    'id' => 'forgot__email-label',
                ],
            ])
            ->add('resetPassword', SubmitType::class, [
                'label' => $this->translator->trans('admin.reset_password'),
                'attr'  => [
                    'class' => 'button button--positive',
                ],
            ])
        ;
    }
}
