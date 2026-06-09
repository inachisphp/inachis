<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds an amdin login form
 * 
 * @extends AbstractType<array{
 *     loginUsername?: string,
 *     loginPassword?: string,
 *     logIn?: string,
 * }>
 */
class LoginType extends AbstractType
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
     *     loginUsername?: string,
     *     loginPassword?: string,
     *     logIn?: string,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('loginUsername', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'form-login__username-label',
                    'aria-required'   => 'true',
                    'autofocus'       => 'true',
                    'class'           => 'text',
                    'id'              => 'form-login__username',
                    'placeholder'     => $this->translator->trans('admin.label.username', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.username', [], 'messages'),
                'label_attr' => [
                    'id' => 'form-login__username-label',
                ],
            ])
            ->add('loginPassword', PasswordType::class, [
                'attr' => [
                    'aria-labelledby' => 'form-login__password-label',
                    'aria-required'    => 'true',
                    'class'            => 'text',
                    'id'               => 'form-login__password',
                    'placeholder'      => $this->translator->trans('admin.label.password'),
                ],
                'label'      => $this->translator->trans('admin.label.password'),
                'label_attr' => [
                    'id' => 'form-login__password-label',
                ],
                'toggle_password' => true,
            ])
//            ->add('rememberMe', CheckboxType::class, [
//                'attr' => [
//                    'class' => 'checkbox'
//                ],
//                'required' => false,
//                'value' => '1',
//                'label' => $this->translator->trans('admin.label.remember_me'),
//            ])
            ->add('logIn', SubmitType::class, [
                'label' => sprintf(
                    '<span>%s</span> <i class="material-icons">arrow_forward</i>',
                    $this->translator->trans('admin.button.login')
                ),
                'label_html' => true,
                'attr'  => [
                    'class' => 'button button--positive',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => Login::class,
            'csrf_token_id' => 'authenticate',
        ]);
    }
}
