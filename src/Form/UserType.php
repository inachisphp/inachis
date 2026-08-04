<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Inachis\Entity\Security\Role;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\Provider\TimezoneChoices;
use Inachis\Security\Authorisation\PermissionResolver;
use Inachis\Service\User\ProfileColorPalette;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for creating and editing users.
 *
 * @extends AbstractType<User>
 */
class UserType extends AbstractType
{
    /**
     * Creates a new instance of {@link UserType}.
     *
     * @param TranslatorInterface $translator The translator service
     * @param Security            $security   The security service
     */
    public function __construct(
        private PermissionResolver $permissionResolver,
        private TranslatorInterface $translator,
        private Security $security,
    ) {
    }

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string, mixed>            $options The form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $newUser = !isset($options['data'])
            || !($options['data'] instanceof User)
            || null === $options['data']->getId();
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new \LogicException();
        }
        $isCurrentUser = $currentUser->getId() === $options['data']->getId();

        $allowEdit = $this->permissionResolver->hasPermission(
            $currentUser,
            PermissionResource::USER,
            $newUser ? PermissionAction::CREATE : PermissionAction::EDIT,
        );

        $allowDelete = $this->permissionResolver->hasPermission(
            $currentUser,
            PermissionResource::USER,
            PermissionAction::DELETE,
        );

        $builder
            ->add('username', $newUser ? TextType::class : HiddenType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__username__label',
                    'autofocus' => $newUser,
                    'class' => 'text inline_label',
                    'placeholder' => 'Enter a unique username',
                    'readOnly' => !$newUser,
                ],
                'label' => 'Username',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__username__label',
                ],
                'required' => true,
            ])
            ->add('displayName', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__displayName__label',
                    'class' => 'text inline_label',
                ],
                'label' => 'Display Name',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__displayName__label',
                ],
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__email__label',
                    'class' => 'text inline_label',
                    'readOnly' => !$newUser,
                ],
                'label' => 'Email Address',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__email__label',
                ],
                'required' => true,
            ])
            ->add('timezone', ChoiceType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__timezone__label',
                    'class' => 'text inline_label',
                ],
                'choices' => (new TimezoneChoices())->getTimezones(),
                'label' => 'Timezone',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__timezone__label',
                ],
                'property_path' => 'preferences.timezone',
            ]);

        if (
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_EDIT')
        ) {
            $builder->add('assignedRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'choice_value' => static fn (?Role $role) => $role?->getId()?->toString(),
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
                'label' => 'Assigned Roles',
                'label_attr' => [
                    'class' => 'inline_label',
                ],
                'required' => false,
                'choice_attr' => static function (Role $role) {
                    return [
                        'class' => 'checkbox',
                    ];
                },
            ]);
        }

        $builder
            ->add('avatar', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> %s',
                    'save',
                    $this->translator->trans('admin.button.save', [], 'messages'),
                ),
                'label_html' => true,
            ])
        ;
        if (!$newUser) {
            if (!$isCurrentUser) {
                $builder
                    ->add('delete', SubmitType::class, [
                        'attr' => [
                            'data-confirm' => 'delete',
                            'data-confirm-text' => 'Yes, delete',
                            'class' => 'btn btn--danger btn--confirm',
                            'data-entity' => 'user',
                            'data-title' => sprintf(
                                '%s (%s)',
                                $options['data']->getDisplayName(),
                                $options['data']->getUsername(),
                            ),
                            'data-warning' => 'This action cannot be undone, and will result in the user no longer being able to access this system.',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            'delete',
                            $this->translator->trans('admin.button.delete', [], 'messages'),
                        ),
                        'label_html' => true,
                    ])
                    ->add('enableDisable', SubmitType::class, [
                        'attr' => [
                            'class' => 'btn btn--secondary',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            $options['data']->isEnabled() ? 'person_off' : 'person_outline',
                            $this->translator->trans($options['data']->isEnabled() ? 'admin.button.disable' : 'admin.button.enable', [], 'messages'),
                        ),
                        'label_html' => true,
                    ]);
            } else {
                $builder->add('color', ChoiceType::class, [
                    'attr' => [
                        'aria-labelledby' => 'user__color__label',
                    ],
                    'choices' => array_combine(ProfileColorPalette::getAll(), ProfileColorPalette::getAll()),
                    'choice_attr' => function ($choice, $key, $value) {
                        return ['data-color' => $value];
                    },
                    'expanded' => true,
                    'label' => 'Color',
                    'label_attr' => [
                        'id' => 'user__color__label',
                    ],
                    'multiple' => false,
                    'property_path' => 'preferences.color',
                ]);
                if ($currentUser->isTotpEnabled()) {
                    $builder->add('disableTotp', SubmitType::class, [
                        'attr' => [
                            'class' => 'btn btn--danger',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            'gpp_bad',
                            'Disable Two-Factor Authentication',
                        ),
                        'label_html' => true,
                    ]);
                    $builder->add('regenerateCodes', SubmitType::class, [
                        'attr' => [
                            'class' => 'btn btn--add',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            'loop',
                            'Generate New Recovery Codes',
                        ),
                        'label_html' => true,
                    ]);
                } else {
                    $builder->add('enableTotp', SubmitType::class, [
                        'attr' => [
                            'class' => 'btn btn--primary',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            'verified_user',
                            'Enable Two-Factor Authentication',
                        ),
                        'label_html' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The options resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
