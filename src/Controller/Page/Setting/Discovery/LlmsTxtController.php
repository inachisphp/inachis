<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Form\LlmsTxtType;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for managing the llms.txt content in the admin settings.
 */
class LlmsTxtController extends AbstractTextFileController
{
    /**
     * Admin interface to edit the llms.txt content.
     *
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @return Response
     */
    #[Route('/incp/settings/llms', name: 'incp_settings_llms')]
    public function edit(
        Request $request,
        SettingRepository $settingRepository
    ): Response {
        return $this->editTextFile($request, $settingRepository);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTextFileForm(
        SettingRepository $settingRepository
    ): FormInterface {
        return $this->createForm(
            LlmsTxtType::class,
            [
                $this->getFormField() => $settingRepository->getValue(
                    $this->getSettingKey()
                ) ?? '',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormField(): string
    {
        return 'llms_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSettingKey(): string
    {
        return 'llms_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplate(): string
    {
        return '/inadmin/page/settings/llms.html.twig';
    }

    protected function getDocumentName(): string
    {
        return 'llms.txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTab(): string
    {
        return 'llms';
    }

    /**
     * {@inheritdoc}
     */
    protected function validateContent(string $content): void
    {
        if ($content === '') {
            $this->addFlash(
                'warning',
                'Your llms.txt file is empty. We will generate a default document.'
            );

            return;
        }

        if (!preg_match('/^#\s+.+$/m', $content)) {
            $this->addFlash(
                'info',
                'Consider adding a Markdown heading as the document title.'
            );
        }

        if (!preg_match('/^>\s*.+$/m', $content)) {
            $this->addFlash(
                'info',
                'Consider adding a short description after the title.'
            );
        }

        if (!preg_match('/^##\s+.+$/m', $content)) {
            $this->addFlash(
                'info',
                'Consider adding sections to help AI systems discover your content.'
            );
        }

        if (!preg_match('/^Sitemap:/mi', $content)) {
            $this->addFlash(
                'info',
                'Consider including a sitemap URL.'
            );
        }
    }
}
