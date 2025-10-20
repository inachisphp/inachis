<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Form;

use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForgotPasswordType extends AbstractType
{
    private ?TranslatorInterface $translator;
    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
//                ->addComponent(new FieldsetType(array(
//                    'legend' => 'Enter your Email address / Username'
//                )))
            ->add('forgot_email', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'form-login__username-label',
                    'aria-required'   => 'true',
                    'autofocus'       => 'true',
                    'class'           => 'text',
                    'id'              => 'form-forgot__email',
                    'placeholder'     => $this->translator->trans('admin.email_example'),
                ],
                'label'      => $this->translator->trans('admin.reset.email_address.label'),
                'label_attr' => [
                    'id' => 'forgot__email-label',
                ],
            ])
//            ->add('captcha', Recaptcha3Type::class, [
//                'constraints' => new Recaptcha3([
//                    'message' => 'karser_recaptcha3.message',
//                    'messageMissingValue' => 'karser_recaptcha3.message_missing_value',
//                ]),
//                'action_name' => 'homepage',
////                'script_nonce_csp' => $nonceCSP,
//                'locale' => $_ENV['APP_LOCALE'] ?: 'en',
//            ])
            ->add('resetPassword', SubmitType::class, [
                'label' => $this->translator->trans('admin.reset_password'),
                'attr'  => [
                    'class' => 'button button--positive',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => Login::class,
        ]);
    }
}
