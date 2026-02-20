<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\NavigationTab;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form for editing a navigation tab
 */
class NavigationTabType extends AbstractType
{

    public function __construct(private TranslatorInterface $translator) {}
    /**
     * Build the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'title_label',
                    'aria-required' => 'true',
                    'autofocus' => 'true',
                    'class' => 'text halfwidth',
                    'placeholder' => $this->translator->trans('admin.navigation.title.placeholder', [], 'messages'),
                    'maxlength' => 100,
                ],
                'label' => $this->translator->trans('admin.navigation.title.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'title_label',
                ],
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required' => 'true',
                    'class' => 'text halfwidth',
                    'placeholder' => $this->translator->trans('admin.navigation.url.placeholder', [], 'messages'),
                    'maxlength' => 255,
                ],
                'label' => $this->translator->trans('admin.navigation.url.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'url_label',
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'isActive_label',
                    'aria-required' => 'false',
                    'class' => 'ui-switch',
                    'data-label-off' => $this->translator->trans('admin.post.properties.visibility.private'),
                    'data-label-on' => $this->translator->trans('admin.post.properties.visibility.public'),
                ],
                'label' => $this->translator->trans('admin.navigation.isActive.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'isActive_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
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
    }

    /**
     * Configure the options for the form
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post form__tab',
            ],
            'data_class' => NavigationTab::class,
        ]);
    }
}