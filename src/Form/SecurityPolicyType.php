<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\Security\SecurityPolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form template for specifying security policy
 * 
 * @extends AbstractType<array{
 *     name?: string,
 *     requireLowercase?: bool,
 *     requireNumber?: bool,
 *     requireSpecial?: bool,
 *     passwordRegex?: string,
 *     passwordExpiryDays:? int,
 *     passwordHistory?: int,
 *     maxFailedLoginAttempts?: int,
 *     lockoutDurationMinutes?: int,
 *     adminRequire2FA?: bool,
 *     superAdminRequire2FA?: bool,
 *     superAdminRequiresWebAuthn?: bool,
 *     stepUpForSensitiveActions?: bool,
 * }>
 */
class SecurityPolicyType extends AbstractType
{
    /**
     * @param FormBuilderInterface<array{
     *     name?: string,
     *     requireLowercase?: bool,
     *     requireNumber?: bool,
     *     requireSpecial?: bool,
     *     passwordRegex?: string,
     *     passwordExpiryDays:? int,
     *     passwordHistory?: int,
     *     maxFailedLoginAttempts?: int,
     *     lockoutDurationMinutes?: int,
     *     adminRequire2FA?: bool,
     *     superAdminRequire2FA?: bool,
     *     superAdminRequiresWebAuthn?: bool,
     *     stepUpForSensitiveActions?: bool,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('minLength', IntegerType::class)
            ->add('requireUppercase', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'required' => false,
            ])
            ->add('requireLowercase', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'required' => false,
            ])
            ->add('requireNumber', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'required' => false,
            ])
            ->add('requireSpecial', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'required' => false,
            ])
            ->add('passwordRegex', TextType::class, ['required' => false])
            ->add('passwordExpiryDays', IntegerType::class, ['required' => false])
            ->add('passwordHistory', IntegerType::class)
            ->add('maxFailedLoginAttempts', IntegerType::class)
            ->add('lockoutDurationMinutes', IntegerType::class)
            ->add('adminRequire2FA', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'label' => 'Require 2FA for Admins',
                'required' => false,
            ])
            ->add('superAdminRequire2FA', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'label' => 'Require 2FA for Super Admins',
                'required' => false,
            ])
            ->add('superAdminRequiresWebAuthn', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'label' => 'Require WebAuthn for Super Admins',
                'required' => false,
            ])
            ->add('stepUpForSensitiveActions', CheckboxType::class, [
                'attr' => [
                    'class'           => 'ui-switch',
                    'data-label-off'  => 'No',
                    'data-label-on'   => 'Yes',
                ],
                'label' => 'Require Step Up for Sensitive Actions',
                'required' => false,
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecurityPolicy::class,
        ]);
    }
}