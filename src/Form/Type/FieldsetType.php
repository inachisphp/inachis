<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldsetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // execute the callable with our FormBuilder passed as an argument
        $options['types']($builder);
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (false !== $options['legend']) {
            $view->vars['legend'] = $options['legend'];
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'legend' => false,
            'virtual' => true,
            'types' => null,
        ])
            ->setAllowedTypes('types', 'callable')
            ->setRequired('types');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'fieldset';
    }
}
