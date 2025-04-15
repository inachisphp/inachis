<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostType extends AbstractType
{
    private $router;
    private $transformer;
    private $translator;

    public function __construct(
        TranslatorInterface $translator,
        RouterInterface $router,
        ArrayCollectionToArrayTransformer $transformer
    ) {
        $this->router = $router;
        $this->translator = $translator;
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $categories = $options['data']->getCategories();
        $tags = $options['data']->getTags();
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby'  => 'title_label',
                    'aria-required'    => 'true',
                    'data-tip-content' => '<strong>Required.</strong> This will also be used in the URL for your content. You can click on the link icon to adjust the URL.',
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
            //value="{% if post.urls is not empty %}{{ post.urls|first.link }}{% endif %}"
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required'   => 'false',
                    'class' => 'halfwidth',
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
                    'data-tip-content' => $this->translator->trans('admin.tip.post.postDate', [], 'messages'),
                    'data-tip-title'   => $this->translator->trans('admin.tip.title.post.postDate', [], 'messages'),
                ],
                'format' => 'dd/MM/yyyy HH:mm',
                'html5'  => false,
                'label'  => $options['data']->getPostDate() < new \DateTime() ?
                    $this->translator->trans('admin.label.post.postDate-past', [], 'messages') :
                    $this->translator->trans('admin.label.post.postDate-future', [], 'messages'),
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
                'choices'      => $options['data']->getCategories()->toArray(),
                'class'        => Category::class,
                'attr'         => [
                    'aria-labelledby'  => 'categories_label',
                    'aria-required'    => 'false',
                    'class'            => 'js-select halfwidth',
                    'data-placeholder' => $this->translator->trans('admin.placeholder.post.categories', [], 'messages'),
                    'data-tip-content' => $this->translator->trans('admin.tip.content.post.categories', [], 'messages'),
                    'data-url'         => $this->router->generate('app_admindialog_getcategorymanagerlistcontent'),
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
                    'data-tip-content' => $this->translator->trans('admin.tip.content.post.tags', [], 'messages'),
                    'data-url'         => $this->router->generate('app_admindialog_gettagmanagerlistcontent'),
//                    'selected'         => 'selected',
                ],
                'choices'      => $options['data']->getTags()->toArray(),
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
            ->add('latlong', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'latlong_label',
                    'aria-required' => 'false',
                    'class' => 'ui-map',
                    'data-google-key' => '{{ settings.google.key }}',
                    'data-tip-content' => 'Please enter a location name, postcode, zip code, or longitude/latitude to search for the location.',
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
                'label'      => $this->translator->trans('admin.label.post.teaser', [], 'messages'),
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
                'label' => $this->translator->trans('admin.button.save', [], 'messages'),
            ])
            ->add('publish', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--info',
                ],
                'label' => $this->translator->trans('admin.button.publish', [], 'messages'),
            ])
            ->add('delete', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--negative',
                ],
                'label' => $this->translator->trans('admin.button.delete', [], 'messages'),
            ]);
//        $builder->get('tags')->addModelTransformer($this->transformer);
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
