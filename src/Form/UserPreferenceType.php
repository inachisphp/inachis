<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Form;

use Inachis\Entity\User\UserPreference;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for creating and editing user preferences.
 *
 * @extends AbstractType<UserPreference>
 */
class UserPreferenceType extends AbstractType
{
    /**
     * Creates a new instance of {@link UserPreferenceType}.
     *
     * @param TranslatorInterface $translator The translator service
     * @param Security            $security   The security service
     */
    public function __construct(
        protected TranslatorInterface $translator,
        protected Security $security,
    ) {
    }

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface<UserPreference|null> $builder The form builder
     * @param array<string, mixed>                      $options The form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('theme', ChoiceType::class, [
                'choices' => [
                    'Light' => 'light',
                    'Dark' => 'dark',
                    'Auto' => 'auto',
                ],
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value): array {
                    $val = is_string($value) ? $value : '';
                    $icons = [
                        'light' => 'light_mode',
                        'dark' => 'dark_mode',
                        'auto' => 'brightness_auto',
                    ];

                    return [
                        'class' => 'theme-option',
                        'data-icon' => $icons[$val] ?? '',
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
                    'class' => 'ui-switch switch-btn-input',
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
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value): array {
                    $val = is_string($value) ? $value : '';

                    return [
                        'class' => 'fontSizePreview-'.$val,
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
                    'Dyslexic' => 'dyslexic',
                ],
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value): array {
                    $val = is_string($value) ? $value : '';
                    $icons = [
                        'sans' => 'font_download',
                        'serif' => 'font_download',
                        'mono' => 'font_download',
                        'dyslexic' => 'accessibility_new',
                    ];

                    return [
                        'class' => 'fontFamilyPreview-'.$val,
                        'data-icon' => $icons[$val] ?? 'font_download',
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
                    'Comfort' => 'comfort',
                    'Spacious' => 'spacious',
                ],
                'choice_attr' => function (mixed $choice, mixed $key, mixed $value): array {
                    $val = is_string($value) ? $value : '';

                    return [
                        'class' => 'lineHeightPreview-'.$val,
                        'data-icon' => 'line_weight',
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
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> %s',
                    'save',
                    $this->translator->trans('admin.btn.save', [], 'messages'),
                ),
                'label_html' => true,
            ]);
    }

    /**
     * Configures the options for the form.
     *
     * @param OptionsResolver $resolver The options resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreference::class,
        ]);
    }
}
