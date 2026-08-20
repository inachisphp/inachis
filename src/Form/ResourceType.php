<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Inachis\Entity\Media\AbstractFile;
use Inachis\Entity\Media\Download;
use Inachis\Entity\Media\Image;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ResourceType.
 *
 * @extends AbstractType<AbstractFile>
 */
class ResourceType extends AbstractType
{
    /**
     * Constructor for ResourceType.
     */
    public function __construct(
        private readonly PermissionResolver $permissionResolver,
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
    ) {
    }

    /**
     * Builds the form.
     *
     * @param FormBuilderInterface<AbstractFile|null> $builder
     * @param array<string, mixed>                    $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        if ($options['data'] instanceof Image) {
            $type = PermissionResource::IMAGE;
        } elseif ($options['data'] instanceof Download) {
            $type = PermissionResource::DOWNLOAD;
        } else {
            throw new \InvalidArgumentException('Unrecognised content type');
        }
        $isNew = null === $options['data']->getId();

        $allowEdit = $this->permissionResolver->hasPermission(
            $user,
            $type,
            PermissionAction::EDIT,
        );
        $allowDelete = $this->permissionResolver->hasPermission(
            $user,
            $type,
            PermissionAction::DELETE,
        );

        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'resource__title__label',
                    'class' => 'text full-width',
                    'maxlength' => 255,
                ],
                'disabled' => !$allowEdit,
                'label' => $this->translator->trans('admin.resources.title.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'resource__title__label',
                ],
            ]);
        if (PermissionResource::IMAGE === $type) {
            $builder
                ->add('altText', TextareaType::class, [
                    'attr' => [
                        'aria-labelledby' => 'resource__altText__label',
                        'class' => 'full-width',
                        'maxlength' => 255,
                        'rows' => 2,
                    ],
                    'disabled' => !$allowEdit,
                    'label' => $this->translator->trans('admin.resources.altText.label', [], 'messages'),
                    'label_attr' => [
                        'id' => 'resource__altText__label',
                    ],
                ]);
        } elseif ($options['data'] instanceof Download) {
            // Build dynamic human-readable string: "PDF, ZIP, TAR, GZ, DOC, etc."
            $allowedExts = array_map(
                static fn (string $ext): string => strtoupper(ltrim($ext, '.')),
                Download::ALLOWED_TYPES,
            );

            $limit = 5;
            $typesPreview = implode(', ', array_slice($allowedExts, 0, $limit));
            if (count($allowedExts) > $limit) {
                $typesPreview .= ', etc.';
            }

            $builder->add('file', FileType::class, [
                'label' => $isNew ? 'Upload File' : 'Replace File',
                'mapped' => false,
                'required' => $isNew,
                'attr' => [
                    'class' => 'filepond',
                    'data-max-file-size' => '50MB',
                ],
                'constraints' => [
                    new File(
                        maxSize: Download::MAX_FILESIZE.'M',
                        mimeTypes: Download::ALLOWED_MIME_TYPES,
                        mimeTypesMessage: sprintf(
                            'Please upload a valid file type (%s).',
                            $typesPreview,
                        ),
                    ),
                ],
            ]);
        }
        $builder
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'resource__description__label',
                    'class' => 'full-width',
                    'maxlength' => 2000,
                    'rows' => 5,
                ],
                'disabled' => !$allowEdit,
                'label' => $this->translator->trans('admin.resources.caption.label', [], 'messages'),
                'label_attr' => [
                    'id' => 'resource__description__label',
                ],
            ]);
        if ($allowEdit) {
            $builder
                ->add('generate_alt_text', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn btn--ai',
                        'id' => 'generate_alt_text',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'auto_awesome',
                        $this->translator->trans('admin.resources.generateAlt.label', [], 'messages'),
                    ),
                    'label_html' => true,
                ])
                ->add('submit', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn--primary',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'save',
                        $this->translator->trans('admin.button.save', [], 'messages'),
                    ),
                    'label_html' => true,
                ]);
        }
        if ($allowDelete) {
            $builder->add('delete', SubmitType::class, [
                'attr' => [
                    'data-confirm' => 'delete',
                    'data-confirm-text' => 'Yes, delete',
                    'class' => 'btn btn--danger btn--confirm',
                    'data-entity' => 'image',
                    'data-title' => $options['data'] instanceof AbstractFile ? $options['data']->getTitle() : 'Unknown',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> <span>%s</span>',
                    'delete',
                    $this->translator->trans('admin.button.delete', [], 'messages'),
                ),
                'label_html' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'last_modified' => null,
        ]);
    }
}
