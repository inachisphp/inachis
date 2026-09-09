<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base controller for editable text-based discovery documents.
 * 
 * @template TData of array<string, mixed>
 */
abstract class AbstractTextFileController extends AbstractInachisController
{
    /**
     * Create the form used to edit the document.
     * 
     * @return FormInterface<TData>
     */
    abstract protected function createTextFileForm(
        SettingRepository $settingRepository,
    ): FormInterface;

    /**
     * Return the name of the form field containing the document content.
     */
    abstract protected function getFormField(): string;

    /**
     * Return the settings key used to persist the document.
     */
    abstract protected function getSettingKey(): string;

    /**
     * Return the template used to render the page.
     */
    abstract protected function getTemplate(): string;

    /**
     * Return the human-readable document name.
     */
    abstract protected function getDocumentName(): string;

    /**
     * Return the active tab identifier.
     */
    abstract protected function getTab(): string;

    /**
     * Hook for subclasses to perform validation and add advisory warnings.
     */
    protected function validateContent(string $content): void
    {
        // Default: no validation.
    }

    /**
     * Render and process the text file editor.
     */
    protected function editTextFile(
        Request $request,
        SettingRepository $settingRepository,
    ): Response {
        $form = $this->createTextFileForm($settingRepository);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            $fieldValue = $data[$this->getFormField()] ?? '';
            $content = trim(is_string($fieldValue) ? $fieldValue : '');

            $this->validateContent($content);

            $settingRepository->setValue(
                $this->getSettingKey(),
                $content,
            );

            $this->addFlash(
                'success',
                $this->getSuccessMessage(),
            );

            return $this->redirect(
                $request->getUri(),
            );
        }

        $this->viewModel->page->title = $this->getPageTitle();
        $this->viewModel->page->tab = $this->getTab();

        return $this->render(
            $this->getTemplate(),
            [
                'viewModel' => $this->viewModel,
                'form' => $form->createView(),
                $this->getFormField() => $settingRepository->getValue(
                    $this->getSettingKey(),
                ) ?? '',
            ],
        );
    }

    /**
     * Return the page title.
     */
    protected function getPageTitle(): string
    {
        return sprintf(
            '%s Configuration',
            $this->getDocumentName(),
        );
    }

    /**
     * Return the success flash message.
     */
    protected function getSuccessMessage(): string
    {
        return sprintf(
            '%s configuration updated.',
            $this->getDocumentName(),
        );
    }
}
