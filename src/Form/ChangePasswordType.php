<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordType extends AbstractType
{
    private ?TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['password_reset']) {
            $builder
                ->add('current_password', PasswordType::class, [
                    'attr' => [
                        'aria-labelledby' => 'user__current_password__label',
                        'autocomplete' => 'current-password',
                        'class' => 'text full-width',
                        'placeholder' => 'Enter your current password',
                        'required' => true,
                    ],
                    'constraints' => [
                        new UserPassword([
                            'message' => 'The current password is incorrect',
                        ]),
                    ],
                    'label' => sprintf('Current password (Last modified: %s)', $options['last_modified']),
                    'label_attr' => [
                        'id' => 'user__current_password__label'
                    ],
                    'mapped' => false,
                ])
            ;
        } else {
            $builder
                ->add('username', TextType::class, [
                    'attr' => [
                        'aria-labelledby' => 'user__username__label',
                        'aria-required'   => 'true',
                        'autofocus'       => 'true',
                        'class'           => 'text',
                        'placeholder'     => $this->translator->trans('admin.label.username', [], 'messages'),
                    ],
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'label'      => $this->translator->trans('admin.label.username', [], 'messages'),
                    'label_attr' => [
                        'id' => 'user__username__label',
                    ],
                    'mapped' => false,
                ])
                ->add('resetPassword', SubmitType::class, [
                    'label' => sprintf(
                        '<span>%s</span> <i class="material-icons">arrow_forward</i>',
                        'Change password',
                    ),
                    'label_html' => true,
                    'attr'  => [
                        'class' => 'button button--positive',
                    ],
                ]);
            ;
        }
        $builder
            ->add('new_password', PasswordType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__new_password__label',
                    'autocomplete' => 'new-password',
                    'class' => 'text full-width',
                    'minlength' => 8,
                    'placeholder' => 'Enter a new password',
                    'required' => true,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Your password should be at least 8 characters',
                        'max' => 4096,
                    ]),
                    new Assert\PasswordStrength([
                        'minScore' => Assert\PasswordStrength::STRENGTH_WEAK,
                        'message' => "Your password must be more complex. See the below guidance.",
                    ]),
                ],
                'label' => 'New password',
                'label_attr' => [
                    'id' => 'user__new_password__label'
                ],
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'last_modified' => null,
            'password_reset' => false,
        ]);
    }
}