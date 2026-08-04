<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
        private PermissionResolver $permissionResolver,
        private Security $security,
        private readonly TranslatorInterface $translator,
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
        array $options,
    ): void {
        $user = $this->security->getUser();
        $allowEdit = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::CRAWLER,
            PermissionAction::EDIT,
        );

        $builder->add('llms_txt', TextareaType::class, [
            'attr' => [
                'aria-labelledby' => 'title_label',
                'autofocus' => 'true',
                'class' => 'text halfwidth',
                'rows' => 20,
                'spellcheck' => 'false',
            ],
            'disabled' => !$allowEdit ? 'disabled' : '',
            'label' => 'Enter the contents of your llms.txt file below. The sitemap and feed URLs will be appended automatically.',
            'label_attr' => [
                'id' => 'title_label',
            ],
            'required' => false,
        ]);
        if ($allowEdit) {
            $builder->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
                'label' => sprintf(
                    '<span class="material-icons">save</span> %s',
                    $this->translator->trans(
                        'admin.button.save',
                        [],
                        'messages',
                    ),
                ),
                'label_html' => true,
            ]);
        }
    }

    /**
     * Configure options for the form.
     */
    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post form__tab',
            ],
        ]);
    }
}
