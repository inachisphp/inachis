<?php

namespace App\Form;

use App\Entity\User;
use App\Util\RandomColorPicker;
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
        $newUser = $options['data']->getUsername() === '';
        $builder
            ->add('username', $newUser ? TextType::class : HiddenType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__username__label',
                    'autofocus' => 'true',
                    'class' => 'text inline_label',
                    'placeholder' => 'Enter a unique username',
                    'readOnly' => !$newUser,
                ],
                'label' => 'Username',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__username__label'
                ],
                'required' => true,
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
                    'readOnly' => !$newUser,
                ],
                'label' => 'Email Address',
                'label_attr' => [
                    'class' => 'inline_label',
                    'id' => 'user__email__label'
                ],
                'required' => true,
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
            ->add('avatar', HiddenType::class)
        ;
        if (!$newUser) {
            $builder->add('color', ChoiceType::class, [
                'attr' => [
                    'aria-labelledby' => 'user__color__label',
                ],
                'choices' => array_combine(RandomColorPicker::getAll(), RandomColorPicker::getAll()),
                'choice_attr' => function ($choice, $key, $value) {
                    return ['data-color' => $value];
                },
                'expanded' => true,
                'label' => 'Color',
                'label_attr' => [
                    'id' => 'user__color__label'
                ],
                'multiple' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
