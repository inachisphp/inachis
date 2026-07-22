<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityTxtType extends AbstractType
{
    /**
     * Constructor
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private PermissionResolver $permissionResolver,
        private Security $security,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @param FormBuilderInterface<array{
     *     security_txt?: string,
     *     submit?: string,
     * }|null> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $user = $this->security->getUser();
        $allowEdit = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::CRAWLER,
            PermissionAction::EDIT
        );

        $builder->add('security_txt', TextareaType::class, [
            'attr' => [
                'aria-labelledby' => 'title_label',
                'autofocus' => true,
                'class' => 'text halfwidth',
                'rows' => 15,
            ],
            'disabled' => !$allowEdit,
            'label' => 'security.txt',
            'label_attr' => [
                'id' => 'title_label',
            ],
            'required' => false,
        ]);
        if ($allowEdit) {
            $builder->add('submit', SubmitType::class, [
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
    }

    /**
     * Configure the options
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post form__tabs',
            ],
        ]);
    }
}
