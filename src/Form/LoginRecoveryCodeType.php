<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
        array $options
    ): void {
        $builder->add(
            'code',
            TextType::class,
            [
                'label' => 'Recovery code',
                'attr' => [
                    'autocomplete' => 'one-time-code',
                    'spellcheck' => 'false',
                    'autocapitalize' => 'characters',
                ],
            ]
        )
        ->add('verify', SubmitType::class, [
            'attr' => [
                'class' => 'button button--positive',
            ],
        ]);
    }
}
