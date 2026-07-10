<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form for editing llms.txt.
 *
 * @extends AbstractType<array{
 *     llms_txt?: string,
 *     submit?: string,
 * }>
 */
class LlmsTxtType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @param FormBuilderInterface<array{
     *     llms_txt?: string,
     *     submit?: string,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('llms_txt', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'title_label',
                    'autofocus' => 'true',
                    'class' => 'text halfwidth',
                    'rows' => 20,
                    'spellcheck' => 'false',
                ],
                'label' => 'Enter the contents of your llms.txt file below. The sitemap and feed URLs will be appended automatically.
',
                'label_attr' => [
                    'id' => 'title_label',
                ],
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
                'label' => sprintf(
                    '<span class="material-icons">save</span> %s',
                    $this->translator->trans(
                        'admin.button.save',
                        [],
                        'messages'
                    )
                ),
                'label_html' => true,
            ]);
    }

    /**
     * Configure options for the form
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post form__tab',
            ],
        ]);
    }
}