<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\User;
use Inachis\Util\RandomColorPicker;
use Inachis\Util\TimezoneChoices;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    private TranslatorInterface $translator;
    private Security $security;

    public function __construct(TranslatorInterface $translator, Security $security)
    {
        $this->translator = $translator;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $newUser = !isset($options['data']) || !($options['data'] instanceof User) || $options['data']->getId() === null;
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
                    'id' => 'user__username__label'
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
                    'id' => 'user__displayName__label'
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
                    'id' => 'user__email__label'
                ],
                'required' => true,
            ])
            ->add('timezone', ChoiceType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__timezone__label',
                    'class' => 'text inline_label',
                ],
                'choices' => (new TimezoneChoices)->getTimezones(),
                'label' => 'Timezone',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__timezone__label',
                ],
                'property_path' => 'preferences.timezone',
            ])
            ->add('avatar', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> %s',
                    'save',
                    $this->translator->trans('admin.button.save', [], 'messages')
                ),
                'label_html' => true,
            ])
        ;
        if (!$newUser) {
            $builder->add('color', ChoiceType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__color__label',
                ],
                'choices' => array_combine(RandomColorPicker::getAll(), RandomColorPicker::getAll()),
                'choice_attr' => function ($choice, $key, $value) {
                    return ['data-color' => $value];
                },
                'expanded' => true,
                'label' => 'Color',
                'label_attr' => [
                    'id' => 'user__color__label'
                ],
                'multiple' => false,
                'property_path' => 'preferences.color',
            ]);
            if ($options['data']->getId() !== $this->security->getUser()->getId()) {
                $builder
                    ->add('delete', SubmitType::class, [
                        'attr' => [
                            'data-confirm' => 'delete',
                            'data-confirm-text' => 'Yes, delete',
                            'class' => 'button button--negative button--confirm',
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
                            $this->translator->trans('admin.button.delete', [], 'messages')
                        ),
                        'label_html' => true,
                    ])
                    ->add('enableDisable', SubmitType::class, [
                        'attr' => [
                            'class' => 'button button--secondary',
                        ],
                        'label' => sprintf(
                            '<span class="material-icons">%s</span> %s',
                            $options['data']->isEnabled() ? 'person_off' : 'person_outline',
                            $this->translator->trans($options['data']->isEnabled() ? 'admin.button.disable' : 'admin.button.enable', [], 'messages')
                        ),
                        'label_html' => true,
                    ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
