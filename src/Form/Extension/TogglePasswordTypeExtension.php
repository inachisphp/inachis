<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ PasswordType::class ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('toggle_password', false);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['type'] = 'password';
        if ($options['toggle_password']) {
            $view->vars['attr']['data-controller'] =
                trim(($view->vars['attr']['data-controller'] ?? '') . ' toggle-password');

            $view->vars['attr']['data-action'] =
                trim(($view->vars['attr']['data-action'] ?? '') . ' toggle-password#toggle');
        }
    }
}
