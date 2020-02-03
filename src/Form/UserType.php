<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__username__label',
                    'class' => 'text full-width',
                    'readOnly' => true,
                ],
                'label' => 'Username',
                'label_attr' => [
                    'id' => 'user__username__label'
                ],
            ])
            ->add('displayName', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__displayName__label',
                    'data-tip-content' => 'How the user will be known',
                    'class' => 'text full-width',
                ],
                'label' => 'Display Name',
                'label_attr' => [
                    'id' => 'user__displayName__label'
                ],
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__email__label',
                    'class' => 'text full-width',
                    'readOnly' => true,
                ],
                'label' => 'Email Address',
                'label_attr' => [
                    'id' => 'user__email__label'
                ],
            ])
            ->add('timezone', ChoiceType::class, [
                'choices' => array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
                'label' => 'Timezone',
            ])
        //            Password
        //            Confirm Password
        //            Timezone
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
