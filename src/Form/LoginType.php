<?php

namespace App\Form;

use App\Entity\Login;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('loginUsername', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'form-login__username-label',
                    'aria-required'   => 'true',
                    'autofocus'       => 'true',
                    'class'           => 'text',
                    'id'              => 'form-login__username',
                    'placeholder'     => $this->translator->trans('admin.label.username', [], 'messages'),
                ],
                'label'      => $this->translator->trans('admin.label.username', [], 'messages'),
                'label_attr' => [
                    'id' => 'form-login__username-label',
                ],
            ])
            ->add('loginPassword', PasswordType::class, [
                'attr' => [
                    'aria-labelled-by' => 'form-login__password-label',
                    'aria-required'    => 'true',
                    'class'            => 'text',
                    'id'               => 'form-login__password',
                    'placeholder'      => $this->translator->trans('admin.label.password'),
                ],
                'label'      => $this->translator->trans('admin.label.password'),
                'label_attr' => [
                    'id' => 'form-login__password-label',
                ],
            ])
//            ->add('rememberMe', CheckboxType::class, [
//                'attr' => [
//                    'class' => 'checkbox'
//                ],
//                'required' => false,
//                'value' => '1',
//                'label' => $this->translator->trans('admin.label.remember_me'),
//            ])
            ->add('logIn', SubmitType::class, [
                'label' => $this->translator->trans('admin.button.login'),
                'attr'  => [
                    'class' => 'button button--positive',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => Login::class,
        ]);
    }
}
