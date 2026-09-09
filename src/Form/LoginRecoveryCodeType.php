<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array{code?: string, verify?: mixed}>
 */
class LoginRecoveryCodeType extends AbstractType
{
    /**
     * @param FormBuilderInterface<array{code?: string, verify?: mixed}|null> $builder
     * @param array<string, mixed>                                            $options
     */
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
