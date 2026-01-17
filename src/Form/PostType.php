<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\{Category,Page,Tag};
use Inachis\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use IntlException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Emoji\EmojiTransliterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTime;

class PostType extends AbstractType
{
    private RouterInterface $router;
    private ArrayCollectionToArrayTransformer $transformer;
    private TranslatorInterface $translator;

    private EmojiTransliterator $emojisTransliterator;

    /**
     * @throws IntlException
     */
    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        ArrayCollectionToArrayTransformer $transformer
    ) {
        $this->router = $router;
        $this->translator = $translator;
        $this->transformer = $transformer;
        $this->emojisTransliterator = EmojiTransliterator::create('github-emoji');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $newItem = empty($options['data']->getId());
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby'  => 'title_label',
                    'aria-required'    => 'true',
                    'autofocus'        => 'true',
                    'class'            => 'editor__title text',
                    'placeholder'      => 'admin.post.title.placeholder',
                ],
                'label'      => 'admin.post.title.label',
                'label_attr' => [
                    'id' => 'title_label',
                    'class' => 'inline_label',
                ],
            ])
            ->add('subTitle', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'subTitle_label',
                    'aria-required'   => 'false',
                    'class' => 'editor__sub-title text inline_label',
                    'placeholder'     => 'admin.post.subTitle.placeholder',
                ],
                'label'      => 'admin.post.subTitle.label',
                'label_attr' => [
                    'id' => 'subTitle_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required'   => 'false',
                    'class' => 'field__wide',
                ],
                'label'      => 'admin.post.url.label',
                'label_attr' => [
                    'id' => 'url_label',
                ],
                'mapped'   => false,
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'content_label',
                    'aria-required'   => 'false',
                    'class' => 'mde_editor',
                ],
                'label'      => 'admin.post.content.label',
                'label_attr' => [
                    'class' => 'hidden',
                    'id'    => 'content_label',
                ],
                'required' => false,
            ])
            ->add('visibility', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'visibility_label',
                    'aria-required'   => 'false',
                    'class'           => 'ui-switch',
                    'data-label-off'  => $this->translator->trans('admin.post.properties.visibility.private'),
                    'data-label-on'   => $this->translator->trans('admin.post.properties.visibility.public'),
                ],
                'label'      => 'admin.post.properties.visibility.label',
                'label_attr' => [
                    'id' => 'visibility_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
            ])
            ->add('postDate', DateTimeType::class, [
                'attr' => [
                    'aria-labelledby'  => 'postDate_label',
                    'aria-required'    => 'false',
                ],
                'format' => 'dd/MM/yyyy HH:mm',
                'html5'  => false,
                'label'  => isset($options['data']) && $options['data']->getPostDate() < new DateTime() ?
                    'admin.post.properties.postDate-past.label' :
                    'admin.post.properties.postDate-future.label',
                'label_attr' => [
                    'id' => 'postDate_label',
                    'class' => 'inline_label',
                ],
                'required' => false,
                'widget'   => 'single_text',
            ])
            ->add('categories', EntityType::class, [
                'choice_attr' => function ($choice, $key, $value) {
                    return ['selected' => 'selected'];
                },
                'choice_label' => 'title',
                'choices'      => isset($options['data']) ?
                    $options['data']->getCategories()->toArray() : [],
                'class'        => Category::class,
                'attr'         => [
                    'aria-labelledby' => 'categories_label',
                    'aria-required' => 'false',
                    'class' => 'js-select halfwidth',
                    'placeholder' => $this->translator->trans('admin.post.properties.categories.placeholder'),
                    'data-url' => $this->router->generate('inachis_dialog_categorydialog_getcategorymanagerlistcontent'),
                    'data-render-description-field' => 'path',
                ],
                'label'      => 'admin.post.properties.categories.label',
                'label_attr' => [
                    'id' => 'categories_label',
                ],
                'mapped'   => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('tags', EntityType::class, [
                'attr' => [
                    'aria-labelledby'  => 'tags_label',
                    'aria-required'    => 'false',
                    'class'            => 'js-select halfwidth',
                    'data-tags'        => 'true',
                    'data-url'         => $this->router->generate('inachis_tags_gettagmanagerlistcontent'),
                ],
                'choices'      => isset($options['data']) ? $options['data']->getTags()->toArray() : [],
                'choice_label' => 'title',
                'choice_attr'  => function ($choice, $key, $value) {
                    return ['selected' => 'selected'];
                },
                'class'      => Tag::class,
                'label'      => 'admin.post.properties.tags.label',
                'label_attr' => [
                    'id' => 'tags_label',
                ],
                'mapped'   => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('language', ChoiceType::class, [
                'choices' => [
                    $this->emojisTransliterator->transliterate(':cn: 简体中文') => 'zh_Hans',
                    $this->emojisTransliterator->transliterate(':uk: English') => 'en_GB',
                    $this->emojisTransliterator->transliterate(':fr: Français') => 'fr_FR',
                ],
                'duplicate_preferred_choices' => false,
                'empty_data'  => 'en_GB',
                'preferred_choices' => [ 'en_GB' ],
            ])
            ->add('latlong', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'latlong_label',
                    'aria-required' => 'false',
                    'class' => 'ui-map',
                    'data-google-key' => '{{ settings.google.key }}',
                ],
                'label' => 'admin.post.properties.location.label',
                'label_attr' => [
                    'id' => 'latlong_label'
                ],
                'required' => false,
            ])
            ->add('featureSnippet', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'teaser_label',
                    'aria-required'   => 'false',
                    'class' => 'full-width',
                    'rows' => 3,
                ],
                'label'      => 'admin.post.sharing.featureSnippet.label',
                'label_attr' => [
                    'id'    => 'teaser_label',
                ],
                'required' => false,
            ])
            ->add('noindex', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'noindex_label',
                    'aria-required' => 'false',
                    'class' => 'checkbox',
                ],
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
                'label' => 'admin.post.sharing.nofollow.label',
                'label_attr' => [
                    'id' => 'nofollow_label',
                ],
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> <span>%s</span>',
                    'save',
                    $this->translator->trans('admin.button.save'),
                ),
                'label_html' => true,
            ])
        ;
        if (!$newItem) {
            $builder
                ->add('modDate', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby'  => 'modDate_label',
                        'aria-readonly'    => 'true',
                        'readOnly' => true,
                    ],
                    'format' => 'dd/MM/yyyy HH:mm',
                    'html5'  => false,
                    'label'  => 'admin.post.properties.modDate.label',
                    'label_attr' => [
                        'id' => 'modDate_label',
                        'class' => 'inline_label',
                    ],
                    'widget'   => 'single_text',
                ])
                ->add('publish', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--info',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'publish',
                        $this->translator->trans('admin.button.publish'),
                    ),
                    'label_html' => true,
                ])
                ->add('delete', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--negative button--confirm',
                        'data-entity' => $options['data']->getType(),
                        'data-title' => $options['data']->getTitle(),
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'delete_forever',
                        $this->translator->trans('admin.button.delete'),
                    ),
                    'label_html' => true,
                ])
            ;
        }
    }

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
