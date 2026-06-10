<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Class for implementing a toggle password component
 */
class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    /**
     * Returns the form component type that this class extends
     *
     * @return iterable<class-string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [ PasswordType::class ];
    }

    /**
     * Sets the default options
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('toggle_password', false);
    }

    /**
     * Configures the view for the component
     *
     * @param FormView $view
     * @param FormInterface<mixed> $form
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['type'] = 'password';
        if (!isset($options['toggle_password'])) {
            return;
        }
        /** @var array<string, mixed> $attr */
        $attr = is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [];
        
        /** @var string $controller */
        $controller = $attr['data-controller'] ?? '';
        $attr['data-controller'] = trim($controller . ' toggle-password');

        /** @var string $action */
        $action = $attr['data-action'] ?? '';
        $attr['data-action'] = trim($action . ' toggle-password#toggle');

        $view->vars['attr'] = $attr;
    }
}
