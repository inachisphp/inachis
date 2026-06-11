<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\Security\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for creating and editing roles
 *
 * @extends AbstractType<Role>
 */
class RoleType extends AbstractType
{
    /**
     * Creates a new instance of {@link RoleType}
     *
     * @param TranslatorInterface $translator The translator service
     */
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    /**
     * Builds the form
     *
     * @param FormBuilderInterface<Role|null> $builder The form builder
     * @param array<string, mixed> $options The form options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !isset($options['data'])
            || !($options['data'] instanceof Role)
            || $options['data']->getId() === null;

        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'role__name__label',
                    'autofocus'       => $isNew,
                    'class'           => 'text inline_label',
                    'placeholder'     => 'Enter a unique role name',
                ],
                'label'      => 'Role Name',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id'    => 'role__name__label',
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'role__description__label',
                    'class'           => 'text inline_label',
                    'placeholder'     => 'Optional description for this role',
                    'rows'            => 3,
                ],
                'label'      => 'Description',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id'    => 'role__description__label',
                ],
                'required' => false,
            ])
            ->add('disableReview', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'role__disableReview__label',
                ],
                'label'      => 'Disable review stage for this role',
                'label_attr' => [
                    'id' => 'role__disableReview__label',
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

        if (!$isNew) {
            $builder->add('delete', SubmitType::class, [
                'attr' => [
                    'class'            => 'button button--negative button--confirm',
                    'data-confirm'     => 'delete',
                    'data-confirm-text' => 'Yes, delete',
                    'data-entity'      => 'role',
                    'data-title'       => $options['data']->getName(),
                    'data-warning'     => 'This action cannot be undone. All users assigned to this role will lose the associated permissions.',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> %s',
                    'delete',
                    $this->translator->trans('admin.button.delete', [], 'messages')
                ),
                'label_html' => true,
            ]);
        }
    }

    /**
     * Configures the options for the form
     *
     * @param OptionsResolver $resolver The options resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
