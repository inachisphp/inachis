<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Tag;
use Inachis\Enum\EditorialStatus;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Provider\TimezoneProvider;
use Inachis\Security\Authorisation\PermissionResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Emoji\EmojiTransliterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form for creating and editing a post.
 *
 * @extends AbstractType<Page>
 */
class PostType extends AbstractType
{
    private EmojiTransliterator $emojisTransliterator;

    /**
     * Constructor.
     *
     * @throws \IntlException
     */
    public function __construct(
        private PermissionResolver $permissionResolver,
        private readonly TimezoneProvider $timezoneProvider,
        private readonly TranslatorInterface $translator,
        private RouterInterface $router,
        private Security $security,
    ) {
        $this->emojisTransliterator = EmojiTransliterator::create('github-emoji');
    }

    /**
     * Build the form.
     *
     * @param FormBuilderInterface<Page|null> $builder
     * @param array<string, mixed>            $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        $userTimezone = $this->timezoneProvider->getForUser($user);

        $newItem = !$options['data'] instanceof Page || empty($options['data']->getId());
        $isScheduled = $options['data'] instanceof Page && $options['data']->getPostDate() > new \DateTimeImmutable('now');

        $allowEdit = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::PAGE,
            !$newItem ? PermissionAction::EDIT : PermissionAction::CREATE,
        );
        $showReview = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::PAGE,
            PermissionAction::REVIEW,
        )
            && $options['data'] instanceof Page
            && EditorialStatus::DRAFT == $options['data']->getStatus()
        ;
        $showPublish = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::PAGE,
            PermissionAction::PUBLISH,
        )
            && $options['data'] instanceof Page
            && EditorialStatus::REVIEW == $options['data']->getStatus()
        ;
        $showDelete = $this->permissionResolver->hasPermission(
            $user,
            PermissionResource::PAGE,
            PermissionAction::DELETE,
        );
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'title_label',
                    'aria-required' => 'true',
                    'autofocus' => 'true',
                    'class' => 'editor__title text',
                    'placeholder' => 'admin.post.title.placeholder',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.title.label',
                'label_attr' => [
                    'id' => 'title_label',
                    'class' => 'inline_label',
                ],
            ])
            ->add('subTitle', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'subTitle_label',
                    'aria-required' => 'false',
                    'class' => 'editor__sub-title text inline_label',
                    'placeholder' => 'admin.post.subTitle.placeholder',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.subTitle.label',
                'label_attr' => [
                    'id' => 'subTitle_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required' => 'false',
                    'class' => 'field__wide',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.url.label',
                'label_attr' => [
                    'id' => 'url_label',
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'content_label',
                    'aria-required' => 'false',
                    'class' => 'mde_editor',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.content.label',
                'label_attr' => [
                    'class' => 'hidden',
                    'id' => 'content_label',
                ],
                'required' => false,
            ])
            ->add('visible', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'visible_label',
                    'aria-required' => 'false',
                    'class' => 'ui-switch',
                    'data-label-off' => $this->translator->trans('admin.post.properties.visibility.private'),
                    'data-label-on' => $this->translator->trans('admin.post.properties.visibility.public'),
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.properties.visibility.label',
                'label_attr' => [
                    'id' => 'visible_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
            ->add('showTableOfContents', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'showTableOfContents_label',
                    'aria-required' => 'false',
                    'class' => 'ui-switch',
                    'data-label-off' => $this->translator->trans('admin.post.properties.showTableOfContents.off'),
                    'data-label-on' => $this->translator->trans('admin.post.properties.showTableOfContents.on'),
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.properties.showTableOfContents.label',
                'label_attr' => [
                    'id' => 'showTableOfContents_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
            ->add('postDate', DateTimeType::class, [
                'attr' => [
                    'aria-labelledby' => 'postDate_label',
                    'aria-required' => 'false',
                    'class' => 'ui-datepicker',
                    'data-format' => 'dd/mm/yyyy HH:ii',
                    'data-on-change' => 'updatePostUrl',
                ],
                'disabled' => !$allowEdit,
                'format' => 'dd/MM/yyyy HH:mm',
                'html5' => false,
                'input' => 'datetime_immutable',
                'label' => isset($options['data'])
                    && $options['data'] instanceof Page
                    && $options['data']->getPostDate() < new \DateTimeImmutable() ?
                        'admin.post.properties.postDate-past.label' :
                        'admin.post.properties.postDate-future.label',
                'label_attr' => [
                    'id' => 'postDate_label',
                    'class' => 'inline_label',
                ],
                'model_timezone' => 'UTC',
                'view_timezone' => $userTimezone,
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('expireDate', DateTimeType::class, [
                'attr' => [
                    'aria-labelledby' => 'expireDate_label',
                    'aria-required' => 'false',
                    'class' => 'ui-datepicker',
                    'data-format' => 'dd/mm/yyyy HH:ii',
                ],
                'disabled' => !$allowEdit,
                'format' => 'dd/MM/yyyy HH:mm',
                'html5' => false,
                'input' => 'datetime_immutable',
                'label' => isset($options['data'])
                    && $options['data'] instanceof Page
                    && '' != $options['data']->getExpireDate()
                    && $options['data']->getExpireDate() < new \DateTimeImmutable() ?
                        'admin.post.properties.expireDate-past.label' :
                        'admin.post.properties.expireDate-future.label',
                'label_attr' => [
                    'id' => 'expireDate_label',
                    'class' => 'inline_label',
                ],
                'model_timezone' => 'UTC',
                'view_timezone' => $userTimezone,
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('categories', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'categories_label',
                    'aria-required' => 'false',
                    'class' => 'js-select halfwidth',
                    'placeholder' => $this->translator->trans('admin.post.properties.categories.placeholder'),
                    'data-url' => $this->router->generate('inachis_dialog_categorydialog_getcategorymanagerlistcontent'),
                    'data-render-description-field' => 'path',
                    'data-selected-options' => json_encode(
                        array_map(
                            static fn (Category $category) => [
                                'value' => (string) $category->getId(),
                                'text' => $category->getTitle(),
                            ],
                            $options['data'] instanceof Page
                                ? $options['data']->getCategories()->toArray()
                                : [],
                        ),
                        JSON_THROW_ON_ERROR,
                    ),
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.properties.categories.label',
                'label_attr' => [
                    'id' => 'categories_label',
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('tags', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'tags_label',
                    'aria-required' => 'false',
                    'class' => 'js-select halfwidth',
                    'data-selected-options' => json_encode(
                        array_map(
                            static fn (Tag $tag) => [
                                'value' => (string) $tag->getId(),
                                'text' => $tag->getTitle(),
                            ],
                            $options['data'] instanceof Page
                                ? $options['data']->getTags()->toArray()
                                : [],
                        ),
                        JSON_THROW_ON_ERROR,
                    ),
                    'data-tags' => 'true',
                    'data-url' => $this->router->generate('api_tags_list'),
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.properties.tags.label',
                'label_attr' => [
                    'id' => 'tags_label',
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('language', ChoiceType::class, [
                'choices' => [
                    $this->emojisTransliterator->transliterate(':cn: 简体中文') => 'zh_Hans',
                    $this->emojisTransliterator->transliterate(':uk: English') => 'en_GB',
                    $this->emojisTransliterator->transliterate(':fr: Français') => 'fr_FR',
                ],
                'disabled' => !$allowEdit,
                'duplicate_preferred_choices' => false,
                'empty_data' => 'en_GB',
                'preferred_choices' => ['en_GB'],
            ])
            ->add('latlong', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'latlong_label',
                    'aria-required' => 'false',
                    'class' => 'ui-map',
                    'data-google-key' => '{{ viewModel.settings.google.key }}',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.properties.location.label',
                'label_attr' => [
                    'id' => 'latlong_label',
                ],
                'required' => false,
            ])
            ->add('featureImage', HiddenType::class, [
                'disabled' => !$allowEdit,
                'mapped' => false,
                'required' => false,
            ])
            // ->add('featureImage', EntityType::class, [
            //     'class' => Image::class,
            //     'choice_label' => 'filename',
            //     'choice_value' => static fn (?Image $image) => $image?->getId()?->toString(),
            //     'required' => false,
            // ])
            ->add('featureSnippet', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'teaser_label',
                    'aria-required' => 'false',
                    'class' => 'full-width',
                    'rows' => 3,
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.sharing.featureSnippet.label',
                'label_attr' => [
                    'id' => 'teaser_label',
                ],
                'required' => false,
            ])
            ->add('noindex', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'noindex_label',
                    'aria-required' => 'false',
                    'class' => 'checkbox',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.sharing.noindex.label',
                'label_attr' => [
                    'id' => 'noindex_label',
                ],
                'required' => false,
            ])
            ->add('nofollow', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'nofollow_label',
                    'aria-required' => 'false',
                    'class' => 'checkbox',
                ],
                'disabled' => !$allowEdit,
                'label' => 'admin.post.sharing.nofollow.label',
                'label_attr' => [
                    'id' => 'nofollow_label',
                ],
                'required' => false,
            ]);
        if ($allowEdit) {
            $builder->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--primary',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> <span>%s</span>',
                    'save',
                    $this->translator->trans('admin.button.save'),
                ),
                'label_html' => true,
            ]);
        }
        if (!$newItem) {
            $builder
                ->add('updatedAt', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby' => 'updatedAt_label',
                        'aria-readonly' => 'true',
                        'readOnly' => true,
                    ],
                    'disabled' => true,
                    'format' => 'dd/MM/yyyy HH:mm',
                    'html5' => false,
                    'input' => 'datetime_immutable',
                    'label' => 'admin.post.properties.updatedAt.label',
                    'label_attr' => [
                        'id' => 'updatedAt_label',
                        'class' => 'inline_label',
                    ],
                    'model_timezone' => 'UTC',
                    'view_timezone' => $userTimezone,
                    'widget' => 'single_text',
                ]);
            if ($showReview) {
                $builder->add('review', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn--secondary',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'rate_review',
                        $this->translator->trans('admin.button.review'),
                    ),
                    'label_html' => true,
                ]);
            }
            if ($showPublish) {
                $builder->add('publish', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn--secondary',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        $isScheduled ? 'schedule' : 'publish',
                        $isScheduled ? $this->translator->trans('admin.button.schedule')
                         : $this->translator->trans('admin.button.publish'),
                    ),
                    'label_html' => true,
                ]);
            }
            if ($showDelete) {
                $builder->add('delete', SubmitType::class, [
                    'attr' => [
                        'data-confirm' => 'delete',
                        'data-confirm-text' => 'Yes, delete',
                        'class' => 'btn btn--danger btn--confirm',
                        'data-entity' => $options['data']->getType(),
                        'data-title' => $options['data']->getTitle(),
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'delete_forever',
                        $this->translator->trans('admin.button.delete'),
                    ),
                    'label_html' => true,
                ]);
            }
        }
    }

    /**
     * Configure the options.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post',
            ],
            'data_class' => Page::class,
        ]);
    }
}
