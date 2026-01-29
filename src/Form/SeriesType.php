<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\Series;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeriesType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
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
                    'placeholder'      => $this->translator->trans('admin.series.title.placeholder', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.series.title.label', [], 'messages'),
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'title_label',
                ],
            ])
            ->add('subTitle', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'subTitle_label',
                    'aria-required'   => 'false',
                    'class' => 'editor__sub-title text',
                    'placeholder'     => $this->translator->trans('admin.series.subTitle.placeholder', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.series.subTitle.label', [], 'messages'),
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'subTitle_label',
                ],
                'required' => false,
            ])
            ->add('url', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'url_label',
                    'aria-required'   => 'false',
                    'class' => 'editor__url text',
                    'pattern' => '[0-9a-zA-ZÀ-ž\-]{4,}',
                    'placeholder'     => $this->translator->trans('admin.series.url.placeholder', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.series.url.label', [], 'messages'),
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
                'label'      => $this->translator->trans('admin.series.description.label', [], 'messages'),
                'label_attr' => [
                    'class' => 'hidden',
                    'id'    => 'description_label',
                ],
                'required' => false,
            ])
        ;
        if (!$newItem) {
            $builder
                ->add('firstDate', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby'  => 'firstDate_label',
                        'aria-required'    => 'false',
                        'class' => 'date-width',
                        'readOnly' => true,
                    ],
                    'format' => 'dd/MM/yyyy', // HH:mm,
                    'html5'  => false,
                    'label'  => $this->translator->trans('admin.series.firstDate.label', [], 'messages'),
                    'label_attr' => [
                        'class' => 'inline_label',
                        'id' => 'firstDate_label',
                    ],
                    'required' => false,
                    'widget'   => 'single_text',

                ])
                ->add('lastDate', DateTimeType::class, [
                    'attr' => [
                        'aria-labelledby'  => 'lastDate_label',
                        'aria-required'    => 'false',
                        'class' => 'date-width',
                        'readOnly' => true,
                    ],
                    'format' => 'dd/MM/yyyy', // HH:mm,
                    'html5'  => false,
                    'label'  => $this->translator->trans('admin.series.lastDate.label', [], 'messages'),
                    'label_attr' => [
                        'class' => 'inline_label',
                        'id' => 'lastDate_label',
                    ],
                    'required' => false,
                    'widget'   => 'single_text',

                ])
                ->add('bulkCreate', ButtonType::class, [
                    'attr' => [
                        'class' => 'button button--add bulk-create__link',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> %s',
                        'create',
                        $this->translator->trans('admin.series.bulk.label', [], 'messages')
                    ),
                    'label_html' => true,
                ])
                ->add('addItem', ButtonType::class, [
                    'attr' => [
                        'class' => 'button button--info content-selector__link',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> %s',
                        'playlist_add',
                        $this->translator->trans('admin.series.add.label', [], 'messages')
                    ),
                    'label_html' => true,
                ])
                ->add('visibility', CheckboxType::class, [
                    'attr' => [
                        'aria-labelledby' => 'visibility_label',
                        'aria-required'   => 'false',
                        'class'           => 'ui-switch',
                        'data-label-off'  => 'private',
                        'data-label-on'   => 'public',
                    ],
                    'label'      => $this->translator->trans('admin.series.visibility.label', [], 'messages'),
                    'label_attr' => [
                        'id' => 'visibility_label',
                        'class' => 'inline_label',
                    ],
                    'required' => false,
                ])
            ;
        }
        $builder
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
//            ->add('publish', SubmitType::class, [
//                'attr' => [
//                    'class' => 'button button--info',
//                ],
//                'label' => $this->translator->trans('admin.button.publish', [], 'messages'),
//            ])
        ;
        if (!$newItem) {
            $builder
                ->add('delete', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--negative button--confirm',
                        'data-entity' => 'series',
                        'data-title' => $options['data']->getTitle(),
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> %s',
                        'delete_forever',
                        $this->translator->trans('admin.button.delete', [], 'messages')
                    ),
                    'label_html' => true,
                ])
                ->add('remove', SubmitType::class, [
                    'attr' => [
                        'class' => 'button button--negative',
                    ],
                    'label' => sprintf(
                        '<span class="material-icons">%s</span> %s',
                        'playlist_remove',
                        $this->translator->trans('admin.button.remove', [], 'messages')
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
                'class' => 'form form__post form__series',
            ],
            'data_class' => Series::class,
        ]);
    }
}
