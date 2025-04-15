<?php

namespace App\Form;

use App\Entity\Series;
use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeriesType extends AbstractType
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
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby'  => 'title_label',
                    'aria-required'    => 'true',
                    'data-tip-content' => '<strong>Required.</strong>',
                    'autofocus'        => 'true',
                    'class'            => 'editor__title text',
                    'placeholder'      => $this->translator->trans('admin.placeholder.series.title', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.series.title', [], 'messages'),
                'label_attr' => [
                    'id' => 'title_label',
                ],
            ])
            ->add('subTitle', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'subTitle_label',
                    'aria-required'   => 'false',
                    'class' => 'editor__sub-title text',
                    'placeholder'     => $this->translator->trans('admin.placeholder.series.subTitle', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.series.subTitle', [], 'messages'),
                'label_attr' => [
                    'id' => 'subTitle_label',
                ],
                'required' => false,
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required'   => 'false',
                    'class' => 'editor__url text',
                    'pattern' => '[0-9a-zA-ZÀ-ž\-]{5,}',
                    'placeholder'     => $this->translator->trans('admin.placeholder.series.url', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.series.url', [], 'messages'),
                'label_attr' => [
                    'id' => 'url_label',
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'description_label',
                    'aria-required'   => 'false',
                    'class' => 'mde_editor',
                ],
                'label'      => $this->translator->trans('admin.label.series.description', [], 'messages'),
                'label_attr' => [
                    'class' => 'hidden',
                    'id'    => 'description_label',
                ],
                'required' => false,
            ])
        ;
        if (!empty($options['data']->getId())) {
            $builder
                ->add('firstDate', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby'  => 'firstDate_label',
                        'aria-required'    => 'false',
                        'class' => 'halfwidth',
                        'data-tip-content' => $this->translator->trans('admin.tip.series.firstDate', [], 'messages'),
                        'data-tip-title'   => $this->translator->trans('admin.tip.title.series.firstDate', [], 'messages'),
                        'readOnly' => true,
                    ],
                    'format' => 'dd/MM/yyyy', // HH:mm',
                    'html5'  => false,
                    'label'  => $this->translator->trans('admin.label.series.firstDate', [], 'messages'),
                    'label_attr' => [
                        'class' => 'date-range__label',
                        'id' => 'firstDate_label',
                    ],
                    'required' => false,
                    'widget'   => 'single_text',

                ])
                ->add('lastDate', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby'  => 'lastDate_label',
                        'aria-required'    => 'false',
                        'class' => 'halfwidth',
                        'data-tip-content' => $this->translator->trans('admin.tip.series.lastDate', [], 'messages'),
                        'data-tip-title'   => $this->translator->trans('admin.tip.title.series.lastDate', [], 'messages'),
                        'readOnly' => true,
                    ],
                    'format' => 'dd/MM/yyyy', // HH:mm',
                    'html5'  => false,
                    'label'  => $this->translator->trans('admin.label.series.lastDate', [], 'messages'),
                    'label_attr' => [
                        'class' => 'date-range__label',
                        'id' => 'lastDate_label',
                    ],
                    'required' => false,
                    'widget'   => 'single_text',

                ])
                ->add('addItem', ButtonType::class, [
                    'attr' => [
                        'class' => 'button button--info content-selector__link',
                    ],
                    'label' => $this->translator->trans('admin.button.addItem', [], 'messages'),
                ])
            ;
        }
        $builder
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
                'label' => $this->translator->trans('admin.button.save', [], 'messages'),
            ])
//            ->add('publish', SubmitType::class, [
//                'attr' => [
//                    'class' => 'button button--info',
//                ],
//                'label' => $this->translator->trans('admin.button.publish', [], 'messages'),
//            ])
            ->add('delete', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--negative',
                ],
                'label' => $this->translator->trans('admin.button.delete', [], 'messages'),
            ])
            ->add('remove', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--negative',
                ],
                'label' => $this->translator->trans('admin.button.remove', [], 'messages'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form form__post form__series',
            ],
            'data_class' => Series::class,
        ]);
    }
}
