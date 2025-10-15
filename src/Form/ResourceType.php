<?php

namespace App\Form;

use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResourceType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'aria-labelledby' => 'resource__title__label',
                    'class' => 'text full-width',
                ],
                'label' => 'Title',
                'label_attr' => [
                    'id' => 'resource__title__label'
                ],

            ])
            ->add('altText', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'resource__altText__label',
                    'class' => 'full-width',
                    'data-tip-content' => 'This is important as it is used by screen readers to improve accessibility',
                    'rows' => 2,
                ],
                'label' => 'Alt Text',
                'label_attr' => [
                    'id' => 'resource__altText__label'
                ],

            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'aria-labelledby' => 'resource__description__label',
                    'class' => 'full-width',
                    'rows' => 5,
                ],
                'label' => 'Caption',
                'label_attr' => [
                    'id' => 'resource__description__label'
                ],

            ])
            ->add('generate_alt_text', ButtonType::class, [
                'attr' => [
                    'class' => 'button button--ai',
                    'id' => 'generate_alt_text',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> <span>%s</span>',
                    'auto_awesome',
                    'Generate Alt Text',
                ),
                'label_html' => true,
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--positive',
                ],
                'label' => sprintf(
                    '<span class="material-icons">%s</span> <span>%s</span>',
                    'save',
                    $this->translator->trans('admin.button.save', [], 'messages')
                ),
                'label_html' => true,
            ])
            ->add('delete', SubmitType::class, [
                'attr' => [
                    'class' => 'button button--negative',
                ],
                'label' => $this->translator->trans('admin.button.delete', [], 'messages'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'last_modified' => null,
        ]);
    }
}