<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\AbstractTextFileController;
use Inachis\Form\SecurityTxtType;
use Inachis\Repository\System\SettingRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityTxtController extends AbstractTextFileController
{
    /**
     * Edit the security.txt policy
     *
     * @param Request $request
     * @param SettingRepository $settingRepository
     * @return Response
     */
    #[Route(
        '/incc/settings/security',
        name: 'incc_settings_security'
    )]
    public function edit(
        Request $request,
        SettingRepository $settingRepository
    ): Response {
        return $this->editTextFile(
            $request,
            $settingRepository
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createTextFileForm(
        SettingRepository $settingRepository
    ): FormInterface {
        return $this->createForm(
            SecurityTxtType::class,
            [
                'security_txt' =>
                    $settingRepository->getValue('security_txt')
                    ?? $this->getDefaultTemplate(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormField(): string
    {
        return 'security_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSettingKey(): string
    {
        return 'security_txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplate(): string
    {
        return '/inadmin/page/settings/security.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageTitle(): string
    {
        return 'security.txt Configuration';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTab(): string
    {
        return 'security';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDocumentName(): string
    {
        return 'security.txt';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectRoute(): string
    {
        return 'incc_settings_security';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(): string
    {
        return 'security.txt configuration updated.';
    }

    /**
     * {@inheritdoc}
     */
    protected function validateContent(string $content): void
    {
        if ($content === '') {
            $this->addFlash(
                'warning',
                'Your security.txt file is empty.'
            );

            return;
        }

        if (!preg_match('/^Contact:\s*\S+/mi', $content)) {
            $this->addFlash(
                'warning',
                'security.txt should contain at least one Contact field.'
            );
        }

        if (!preg_match('/^Expires:\s*(.+)$/mi', $content, $matches)) {
            $this->addFlash(
                'warning',
                'security.txt should contain an Expires field.'
            );
            return;
        }

        $expires = \DateTimeImmutable::createFromFormat(
            'Y-m-d\TH:i:s\Z',
            trim($matches[1])
        );

        if (!$expires) {
            $this->addFlash(
                'warning',
                'The Expires field must use ISO 8601 UTC format, for example 2027-07-10T00:00:00Z.'
            );

            return;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if ($expires <= $now) {
            $this->addFlash(
                'warning',
                'The security.txt Expires date has passed.'
            );
        } elseif ($expires < $now->modify('+30 days')) {
            $this->addFlash(
                'info',
                'The security.txt Expires date is less than 30 days away.'
            );
        }
    }

    /**
     * Generate a default security.txt template for easy editing when a policy
     * is not yet defined.
     *
     * @return string
     */
    private function getDefaultTemplate(): string
    {
        $expires = (new \DateTimeImmutable('+1 year'))
            ->setTime(0, 0)
            ->format('Y-m-d\TH:i:s\Z');

        return <<<TXT
    # Security Policy

    Contact: mailto:

    Expires: {$expires}
    Preferred-Languages: en

    TXT;
    }
}
