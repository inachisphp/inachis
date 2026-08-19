<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form\System;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class ContentPurgeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('acknowledge', CheckboxType::class, [
                'attr' => [
                    'class' => 'checkbox',
                ],
                'label' => 'I acknowledge that all user content will be deleted and cannot be recovered',
                'label_attr' => [
                    'class' => 'inline_label',
                ],
                'required' => true,
            ])
            ->add('purge', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn--danger',
                    'data-confirm' => 'purge-user-content',
                    'data-title' => 'Purge User-Generated Content',
                    'data-entity' => 'user-generated content',
                    'data-warning' => 'This will permanently remove all user-generated content from the site. User accounts and authentication data will not be affected. This action cannot be undone.',
                    'data-confirm-text' => 'Purge User-Generated Content',
                ],
                'label' => 'Purge User-Generated Content',
            ]);
    }
}
