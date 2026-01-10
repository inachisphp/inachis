<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form;

use Inachis\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('filename', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__filename__label',
                    'class' => 'text full-width',
                ],
                'label' => 'Image file',
                'label_attr' => [
                    'id' => 'image-uploader__filename__label'
                ],
            ])
            ->add('optimiseImage', CheckboxType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__optimiseImage__label',
                    'checked' => 'checked',
                    'class' => 'checkbox',
                ],
                'label' => 'Optimize image',
                'label_attr' => [
                    'id' => 'image-uploader__optimiseImage__label'
                ],
                'mapped' => false,
            ])
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__title__label',
                    'class' => 'text full-width',
                ],
                'label' => 'Title',
                'label_attr' => [
                    'id' => 'image-uploader__title__label'
                ],

            ])
            ->add('altText', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__altText__label',
                    'class' => 'full-width',
                    'rows' => 1,
                ],
                'label' => 'Alt Text',
                'label_attr' => [
                    'id' => 'image-uploader__altText__label'
                ],

            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__description__label',
                    'class' => 'full-width',
                    'rows' => 2,
                ],
                'label' => 'Caption',
                'label_attr' => [
                    'id' => 'image-uploader__description__label'
                ],

            ])

//            ->add('dimensionX')
//            ->add('dimensionY')
//            ->add('filetype')
//            ->add('filesize')
//            ->add('checksum')
//            ->add('createDate')
//            ->add('modDate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
