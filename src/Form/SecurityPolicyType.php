<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Enum\Security\AuthenticationPolicy;
use Inachis\Enum\Security\PasswordStrengthLevel;
use Inachis\Enum\Security\SensitiveAction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form used to edit a security policy.
 *
 * @extends AbstractType<SecurityPolicy>
 */
class SecurityPolicyType extends AbstractType
{
    /**
     * @param FormBuilderInterface<SecurityPolicy|null> $builder
     * @param array<string, mixed>                      $options
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('name', TextType::class)

            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])

            ->add('minimumPasswordLength', IntegerType::class)

            ->add('maximumPasswordLength', IntegerType::class, [
                'required' => false,
            ])

            ->add('passwordStrength', EnumType::class, [
                'class' => PasswordStrengthLevel::class,
                'choice_label' => static fn (PasswordStrengthLevel $choice) => $choice->label(),
            ])

            ->add('rejectCompromisedPasswords', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'ui-switch',
                    'data-label-on' => 'Yes',
                    'data-label-off' => 'No',
                ],
            ])

            ->add('passwordReuseLimit', IntegerType::class)

            ->add('minimumPasswordAgeDays', IntegerType::class, [
                'required' => false,
            ])

            ->add('passwordLifetimeDays', IntegerType::class, [
                'required' => false,
            ])

            ->add('administratorPolicy', EnumType::class, [
                'class' => AuthenticationPolicy::class,
                'choice_label' => static fn (AuthenticationPolicy $choice) => $choice->label(),
            ])

            ->add('superAdministratorPolicy', EnumType::class, [
                'class' => AuthenticationPolicy::class,
                'choice_label' => static fn (AuthenticationPolicy $choice) => $choice->label(),
            ])

            ->add('requireStepUpAuthentication', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'ui-switch',
                    'data-label-on' => 'Yes',
                    'data-label-off' => 'No',
                ],
            ])

            ->add('stepUpRequiredActions', EnumType::class, [
                'class' => SensitiveAction::class,
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'choice_label' => static fn (SensitiveAction $choice) => $choice->label(),
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => SecurityPolicy::class,
        ]);
    }
}
