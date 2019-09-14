<?php

namespace App\Form;

use App\Entity\Image;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('filename', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__filename__label',
                    'data-tip-content' => 'Must be a linked to a file and not a webpage',
                    'class' => 'text',
                ],
                'label' => 'URL',
                'label_attr' => [
                    'id' => 'image-uploader__filename__label'
                ],
            ])
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__title__label',
                    'class' => 'text',
                ],
                'label' => 'Title',
                'label_attr' => [
                    'id' => 'image-uploader__title__label'
                ],

            ])
            ->add('altText', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__altText__label',
                    'class' => '',
                    'data-tip-content' => 'This is important as it is used by screen readers to improve accessibility',
                ],
                'label' => 'Alt Text',
                'label_attr' => [
                    'id' => 'image-uploader__altText__label'
                ],

            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'image-uploader__description__label',
                    'class' => '',
                ],
                'label' => 'Caption',
                'label_attr' => [
                    'id' => 'image-uploader__description__label'
                ],

            ])

//            ->add('dimensionX')
//            ->add('dimensionY')
//            ->add('description')
//            ->add('filetype')
//            ->add('filesize')
//            ->add('checksum')
//            ->add('createDate')
//            ->add('modDate')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Image::class,
        ]);
    }
}
