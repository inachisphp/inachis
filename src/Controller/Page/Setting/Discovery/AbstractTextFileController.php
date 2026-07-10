<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base controller for editable text-based discovery documents.
 */
abstract class AbstractTextFileController extends AbstractInachisController
{
    /**
     * Create the form used to edit the document.
     *
     * @param SettingRepository $settingRepository
     * @return FormInterface
     */
    abstract protected function createTextFileForm(
        SettingRepository $settingRepository
    ): FormInterface;

    /**
     * Return the name of the form field containing the document content.
     *
     * @return string
     */
    abstract protected function getFormField(): string;

    /**
     * Return the settings key used to persist the document.
     *
     * @return string
     */
    abstract protected function getSettingKey(): string;

    /**
     * Return the template used to render the page.
     *
     * @return string
     */
    abstract protected function getTemplate(): string;

    /**
     * Return the human-readable document name.
     *
     * @return string
     */
    abstract protected function getDocumentName(): string;

    /**
     * Return the active tab identifier.
     *
     * @return string
     */
    abstract protected function getTab(): string;

    /**
     * Hook for subclasses to perform validation and add advisory warnings.
     *
     * @param string $content
     * @return void
     */
    protected function validateContent(string $content): void
    {
        // Default: no validation.
    }

    /**
     * Render and process the text file editor.
     *
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @return Response
     */
    protected function editTextFile(
        Request $request,
        SettingRepository $settingRepository
    ): Response {
        $form = $this->createTextFileForm($settingRepository);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();

            $content = trim(
                (string) ($data[$this->getFormField()] ?? '')
            );

            $this->validateContent($content);

            $settingRepository->setValue(
                $this->getSettingKey(),
                $content
            );

            $this->addFlash(
                'success',
                $this->getSuccessMessage()
            );

            return $this->redirect(
                $request->getUri()
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
                    $this->getSettingKey()
                ) ?? '',
            ]
        );
    }

    /**
     * Return the page title.
     *
     * @return string
     */
    protected function getPageTitle(): string
    {
        return sprintf(
            '%s Configuration',
            $this->getDocumentName()
        );
    }

    /**
     * Return the success flash message.
     *
     * @return string
     */
    protected function getSuccessMessage(): string
    {
        return sprintf(
            '%s configuration updated.',
            $this->getDocumentName()
        );
    }
}
