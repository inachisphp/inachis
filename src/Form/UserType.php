<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', HiddenType::class, [
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
                    'class' => 'text inline_label',
                ],
                'label' => 'Display Name',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__displayName__label'
                ],
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__email__label',
                    'class' => 'text inline_label',
                    'readOnly' => true,
                ],
                'label' => 'Email Address',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__email__label'
                ],
            ])
            ->add('timezone', ChoiceType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__timezone__label',
                    'data-tip-content' => 'How the user will be known',
                    'class' => 'text inline_label',
                ],
                'choices' => array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
                'label' => 'Timezone',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__timezone__label',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
