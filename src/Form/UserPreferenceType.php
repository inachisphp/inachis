<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\UserPreference;
use Inachis\Util\RandomColorPicker;
use Inachis\Util\TimezoneChoices;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserPreferenceType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected Security $security
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('theme', ChoiceType::class, [
                'choices' => [
                    'Light' => 'light',
                    'Dark' => 'dark',
                    'Auto' => 'auto',
                ],
                'choice_attr' => function($choice, $key, $value) {
                    $icons = [
                        'light' => 'light_mode',
                        'dark' => 'dark_mode',
                        'auto' => 'brightness_auto',
                    ];
                    return [
                        'class' => 'theme-option', 
                        // 'id' => 'theme-' . $value,
                        'data-icon' => $icons[$value] ?? '',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'label_attr' => [
                    'class' => 'theme-option',
                    'tabindex' => 0,
                    'aria-checked' => 'false',
                ],
            ])
            ->add('highContrast', CheckboxType::class, [
                'required' => false,
                'label' => 'High Contrast',
                'label_attr' => [
                    'class' => 'inline-label',
                    'id' => 'high_contrast_label',
                ],
                'attr' => [
                    'aria-labelledby' => 'high_contrast_label',
                    'aria-required' => 'false',
                    'class' => 'ui-switch switch-button-input',
                    'data-label-on' => 'enabled',
                    'data-label-off' => 'disabled',
                ],
            ])
            ->add('fontSize', ChoiceType::class, [
                'choices' => [
                    'Default' => 'default',
                    'Larger' => 'larger',
                    'Largest' => 'largest',
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'class' => 'fontSizePreview-' . $value, 
                        'data-icon' => 'format_size',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'label_attr' => [
                    'tabindex' => 0,
                    'aria-checked' => 'false',
                ],
            ])
            ->add('fontFamily', ChoiceType::class, [
                'choices' => [
                    'Sans' => 'sans',
                    'Serif' => 'serif',
                    'Mono' => 'mono',
                    'Dyslexic' => 'dyslexic'
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'class' => 'fontFamilyPreview-' . $value, 
                        'data-icon' => 'format_size',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'label_attr' => [
                    'tabindex' => 0,
                    'aria-checked' => 'false',
                ],
            ])
            ->add('lineHeight', ChoiceType::class, [
                'choices' => [
                    'default' => 'default',
                    'Comfort' => 'comfortable',
                    'Spacious' => 'spacious',
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return [
                        'class' => 'lineHeightPreview-' . $value, 
                        'data-icon' => 'format_size',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'label_attr' => [
                    'tabindex' => 0,
                    'aria-checked' => 'false',
                ],
            ])
            // ->add('timezone', ChoiceType::class, [
            //     'attr' => [
            //         'aria-labelledby' => 'user__timezone__label',
            //         'class' => 'text inline_label',
            //     ],
            //     'choices' => (new TimezoneChoices)->getTimezones(),
            //     'label' => 'Timezone',
            //     'label_attr' => [
            //         'class' => 'inline_label',
            //         'id' => 'user__timezone__label',
            //     ],
            // ])
            // ->add('color', ChoiceType::class, [
            //     'attr' => [
            //         'aria-labelledby' => 'user__color__label',
            //     ],
            //     'choices' => array_combine(RandomColorPicker::getAll(), RandomColorPicker::getAll()),
            //     'choice_attr' => function ($choice, $key, $value) {
            //         return ['data-color' => $value];
            //     },
            //     'expanded' => true,
            //     'label' => 'Color',
            //     'label_attr' => [
            //         'id' => 'user__color__label'
            //     ],
            //     'multiple' => false,
            // ])
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreference::class,
        ]);
    }
}
