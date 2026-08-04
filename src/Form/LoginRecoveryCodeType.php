<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginRecoveryCodeType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'code',
            TextType::class,
            [
                'label' => 'Recovery code',
                'attr' => [
                    'aria-required' => 'true',
                    'autocapitalize' => 'characters',
                    'autocomplete' => 'one-time-code',
                    'class' => 'auth-code-field ',
                    'maxlength' => 9,
                    'pattern' => '[0-9A-Za-z]{4}-[0-9A-Za-z]{4}',
                    'placeholder' => '••••-••••',
                    'required' => true,
                    'spellcheck' => 'false',
                ],
            ],
        )
        ->add('verify', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn--primary',
            ],
        ]);
    }
}
