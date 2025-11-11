<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Form;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
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
                    'placeholder'      => $this->translator->trans('admin.placeholder.post.title', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.post.title', [], 'messages'),
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
                    'placeholder'     => $this->translator->trans('admin.placeholder.post.subTitle', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.post.subTitle', [], 'messages'),
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
                'label'      => $this->translator->trans('admin.label.post.url', [], 'messages'),
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
                'label'      => $this->translator->trans('admin.label.post.content', [], 'messages'),
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
                    'data-label-off'  => 'private',
                    'data-label-on'   => 'public',
                ],
                'label'      => $this->translator->trans('admin.label.post.visibility', [], 'messages'),
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
                'label'  => isset($options['data']) && $options['data']->getPostDate() < new DateTime() ? $this->translator->trans('admin.label.post.postDate-past', [], 'messages') : $this->translator->trans('admin.label.post.postDate-future', [], 'messages'),
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
                'choices'      => isset($options['data']) ? $options['data']->getCategories()->toArray() : [],
                'class'        => Category::class,
                'attr'         => [
                    'aria-labelledby'  => 'categories_label',
                    'aria-required'    => 'false',
                    'class'            => 'js-select halfwidth',
                    'data-placeholder' => $this->translator->trans('admin.placeholder.post.categories', [], 'messages'),
                    'data-url'         => $this->router->generate('app_dialog_categorydialog_getcategorymanagerlistcontent'),
                ],
                'label'      => $this->translator->trans('admin.label.post.categories', [], 'messages'),
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
                    'data-url'         => $this->router->generate('app_tags_gettagmanagerlistcontent'),
                ],
                'choices'      => isset($options['data']) ? $options['data']->getTags()->toArray() : [],
                'choice_label' => 'title',
                'choice_attr'  => function ($choice, $key, $value) {
                    return ['selected' => 'selected'];
                },
                'class'      => Tag::class,
                'label'      => $this->translator->trans('admin.label.post.tags', [], 'messages'),
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
                'label' => 'Location',
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
                'label'      => $this->translator->trans('admin.post.featureSnippet.label', [], 'messages'),
                'label_attr' => [
                    'id'    => 'teaser_label',
                ],
                'required' => false,
            ])
            ->add('sharingMessage', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'sharingMessage_label',
                    'aria-required'   => 'false',
                    'class' => 'halfwidth ui-counter',
                ],
                'label'      => $this->translator->trans('admin.label.post.sharingMessage', [], 'messages'),
                'label_attr' => [
                    'id'    => 'sharingMessage_label',
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
                    $this->translator->trans('admin.button.save', [], 'messages')
                ),
                'label_html' => true,
            ])
        ;
        if (!$newItem) {
            $builder
                ->add('publish', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--info',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'publish',
                        $this->translator->trans('admin.button.publish', [], 'messages')
                    ),
                    'label_html' => true,
                ])
                ->add('delete', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--negative button--confirm',
                        'data-title' => $options['data']->getTitle(),
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> <span>%s</span>',
                        'delete_forever',
                        $this->translator->trans('admin.button.delete', [], 'messages')
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
