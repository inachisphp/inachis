<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Form\RobotsTxtType;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for managing the robots.txt content in the admin settings.
 */
class RobotsTxtController extends AbstractTextFileController
{
    /**
     * Admin interface to edit the robots.txt content.
     */
    #[Route('/incp/settings/robots', name: 'incp_settings_robots')]
    public function edit(
        Request $request,
        SettingRepository $settingRepository,
    ): Response {
        return $this->editTextFile($request, $settingRepository);
    }

    protected function createTextFileForm(
        SettingRepository $settingRepository,
    ): FormInterface {
        return $this->createForm(
            RobotsTxtType::class,
            [
                $this->getFormField() => $settingRepository->getValue(
                    $this->getSettingKey(),
                ) ?? '',
            ],
        );
    }

    protected function getFormField(): string
    {
        return 'robots_txt';
    }

    protected function getSettingKey(): string
    {
        return 'robots_txt';
    }

    protected function getTemplate(): string
    {
        return '/inadmin/page/settings/robots.html.twig';
    }

    protected function getDocumentName(): string
    {
        return 'robots.txt';
    }

    protected function getTab(): string
    {
        return 'robots';
    }

    protected function validateContent(string $content): void
    {
        if (preg_match('/^Disallow:\s*\/\s*$/mi', $content)) {
            $this->addFlash(
                'warning',
                'Your robots.txt blocks the entire site from indexing.',
            );
        }
    }
}
