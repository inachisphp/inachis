<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Inachis\Entity\System\NavigationTab;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form for editing a navigation tab.
 *
 * @extends AbstractType<NavigationTab>
 */
class NavigationTabType extends AbstractType
{
    /**
     * Constructor for the NavigationTabType form.
     */
    public function __construct(
        private PermissionResolver $permissionResolver,
        private Security $security,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Build the form.
     *
     * @param FormBuilderInterface<NavigationTab|null> $builder
     * @param array<string, mixed>                     $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        $newItem = !$options['data'] instanceof NavigationTab
            || empty($options['data']->getId());
        $allowEdit = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::NAVIGATION,
            $newItem ? PermissionAction::CREATE : PermissionAction::EDIT,
        );

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
                'disabled' => !$allowEdit,
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
                'disabled' => !$allowEdit,
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
                'disabled' => !$allowEdit,
                'label' => $this->translator->trans('admin.navigation.isActive.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'isActive_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ]);
        if ($allowEdit) {
            $builder->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> %s',
                    'save',
                    $this->translator->trans('admin.button.save', [], 'messages'),
                ),
                'label_html' => true,
            ]);
        }
    }

    /**
     * Configure the options for the form.
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
