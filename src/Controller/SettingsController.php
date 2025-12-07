<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Entity\Tag;
use App\Entity\Url;
use App\Form\ContentType;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class SettingsController extends AbstractInachisController
{
    #[Route("/incc/settings")]
    public function index(Request $request): Response
    {
        $available_space = disk_free_space(dirname($request->server->get('SCRIPT_FILENAME')));
        $total_space = disk_total_space(dirname($request->server->get('SCRIPT_FILENAME')));
        $this->data['storage']['percent'] = ($total_space - $available_space) / $total_space * 100;
        $this->data['counts']['page'] = $this->entityManager->getRepository(Page::class)->getAllCount();
        $this->data['counts']['series'] = $this->entityManager->getRepository(Series::class)->getAllCount();
        $this->data['counts']['tag'] = $this->entityManager->getRepository(Tag::class)->getAllCount();
        $this->data['counts']['url'] = $this->entityManager->getRepository(Url::class)->getAllCount();
        $form = $this->createForm(ContentType::class);
        $form->handleRequest($request);
        $this->data['form'] = $form->createView();

        $this->data['data_types'] = [
            'raw' => $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames()
        ];
        if (!empty($this->data['data_types']['raw'])) {
            foreach ($this->data['data_types']['raw'] as $type) {
                if (class_exists($type) && method_exists($type, 'isExportable') && $type::isExportable()) {
                    $this->data['data_types'][$type] = $type::getName();
                }
            }
            unset($this->data['data_types']['raw']);
        }

        return $this->render('inadmin/settings.html.twig', $this->data);
    }

    #[Route("/incc/settings/check")]
    public function check(): Response
    {
        $this->data['check'] = [
            'app' => [
                'name' => '',
                'version' => '',
                'version_ok' => '',
                'dir' => [
                    'cache_writable' => '',
                ],
            ],
            'php' => [
                'required_version' => '7.1.0',
                'current_version' => phpversion(),

                'accelerator' => $this->getOpCacheStatus(),
                'default_socket_timeout' => ini_get('default_socket_timeout'),
                'short_tags_disabled' => ini_get('short_open_tag'),
                'session' => [
                    'name' => ini_get('session.name'),
                    'auto_start' => ini_get('session.auto_start'),
                    'cookie_domain' => ini_get('session.cookie_domain'),
                    'cookie_lifetime' => ini_get('session.cookie_lifetime'),
                    'cookie_secure' => ini_get('session.cookie_secure'),
                    'cookie_httponly' => ini_get('session.cookie_httponly'),
                    'hash_bits' => ini_get('session.hash_bits_per_character'),
                    'use_trans_sid' => ini_get('session.use_trans_sid'),
                    'use_only_cookies' => ini_get('session.use_only_cookies'),
                    'cache_expire' => ini_get('session.cache_expire'),
                ],
            ],
            'timezone' => [
                'current' => date_default_timezone_get(),
                'valid' => in_array(date_default_timezone_get(), timezone_identifiers_list()),
            ],
            'extensions' => [
                'iconv' => extension_loaded('iconv'),
                'intl' => extension_loaded('intl'),
            ],
        ];
        $this->data['check']['php']['version_ok'] = version_compare(
            $this->data['check']['php']['current_version'],
            $this->data['check']['php']['required_version'],
            '>'
        );
        return $this->render('inadmin/settings__check.html.twig', $this->data);
    }

    /**
     * @param LoggerInterface $logger
     * @param Request $request
     * @return RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    #[Route("/incc/settings/wipe", methods: [ "POST" ])]
    public function wipe(LoggerInterface $logger, Request $request): RedirectResponse
    {
        if ($request->request->get('confirm', false)) {
            $logger->info('Wiping all content');
            $this->entityManager->getRepository(Image::class)->wipe($logger);
            $this->entityManager->getRepository(Page::class)->wipe($logger);
            $this->entityManager->getRepository(Series::class)->wipe($logger);
            $this->entityManager->getRepository(Tag::class)->wipe($logger);
            $this->entityManager->getRepository(Url::class)->wipe($logger);
        }
        return $this->redirectToRoute('app_settings_index');
    }

    /**
     * @return string
     */
    private function getOpCacheStatus(): string
    {
        if (function_exists('opcache_get_status') && opcache_get_status()) {
            return 'PHP OpCache';
        }
        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            return 'APC';
        }
        return 'n/a';
    }
}
