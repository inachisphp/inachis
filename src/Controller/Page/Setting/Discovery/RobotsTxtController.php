<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
     *
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @return Response
     */
    #[Route('/incp/settings/robots', name: 'incp_settings_robots')]
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
            RobotsTxtType::class,
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
        return 'robots_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSettingKey(): string
    {
        return 'robots_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplate(): string
    {
        return '/inadmin/page/settings/robots.html.twig';
    }

    protected function getDocumentName(): string
    {
        return 'robots.txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTab(): string
    {
        return 'robots';
    }

    /**
     * {@inheritdoc}
     */
    protected function validateContent(string $content): void
    {
        if (preg_match('/^Disallow:\s*\/\s*$/mi', $content)) {
            $this->addFlash(
                'warning',
                'Your robots.txt blocks the entire site from indexing.'
            );
        }
    }
}
