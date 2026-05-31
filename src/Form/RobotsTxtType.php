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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form for editing a navigation tab
 */
class RobotsTxtType extends AbstractType
{

    public function __construct(private TranslatorInterface $translator) {}
    /**
     * Build the form
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('robots_txt', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'title_label',
                    'aria-required' => 'true',
                    'autofocus' => 'true',
                    'class' => 'text halfwidth',
                    'rows' => 15,
                ],
                'label' => $this->translator->trans('admin.setting.robots_txt.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'title_label',
                ],
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
            // 'data_class' => Setting::class,
        ]);
    }
}