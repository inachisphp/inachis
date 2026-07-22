<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\System\SettingRepository;
use Inachis\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PrivacyController extends AbstractInachisController
{
    /**
     * Default key prefix for privacy settings stored in database/cache
     */
    private const SETTING_PREFIX = 'gdpr_';

    // public function __construct(
    //     private readonly SettingRepository $settingRepository,
    //     private readonly UserRepository $userRepository
    // ) {
    // }

    /**
     * Renders the Privacy & GDPR management page
     */
    #[Route('/incc/security/privacy', name: 'incc_security_privacy', methods: ['GET'])]
    public function index(
        SettingRepository $settingRepository,
    ): Response {
        // Enforce VIEW permission
        // $this->denyAccessUnlessGranted('PRIVACY_GDPR', 'VIEW');

        // Fetch all privacy-related settings into an associative array
        $privacySettings = [
            'banner_enabled' => 0,'consent_mode' => 'opt_in',
            'anonymize_ips' => 0,
        ];//$this->getPrivacySettings();

        $this->viewModel->page->title = 'Privacy';
        $this->viewModel->page->tab = 'security';

        return $this->render('inadmin/page/security/gdpr.html.twig', [
            'viewModel' => $this->viewModel,
            'privacy' => $privacySettings,
        ]);
    }

    /**
     * Handles saving Privacy & GDPR configuration
     */
    #[Route('/incc/security/privacy/save', name: 'incc_security_privacy_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        // Enforce EDIT permission
        $this->denyAccessUnlessGranted('PRIVACY_GDPR', 'EDIT');

        if (!$this->isCsrfTokenValid('privacy_save', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('incc_security_privacy');
        }

        $gdprData = $request->request->all('gdpr');

        // Map submitted fields to setting entries
        $allowedSettings = [
            'banner_enabled' => $gdprData['banner_enabled'] ?? '0',
            'consent_mode' => $gdprData['consent_mode'] ?? 'opt_in',
            'banner_message' => $gdprData['banner_message'] ?? '',
            'anonymize_ips' => $gdprData['anonymize_ips'] ?? '1',
            'log_retention_days' => (string) max(7, (int) ($gdprData['log_retention_days'] ?? 90)),
        ];

        foreach ($allowedSettings as $key => $value) {
            $settingName = self::SETTING_PREFIX . $key;
            $this->settingRepository->setSetting($settingName, $value);
        }

        // Flush entity manager updates
        $this->settingRepository->save();

        $this->addFlash('success', 'Privacy and GDPR settings updated successfully.');

        return $this->redirectToRoute('incc_security_privacy');
    }

    /**
     * Handles Subject Access Request (SAR) Personal Data Export
     */
    #[Route('/incc/security/privacy/export-user', name: 'incc_security_privacy_export_user', methods: ['POST'])]
    public function exportUserData(Request $request): Response
    {
        $this->denyAccessUnlessGranted('PRIVACY_GDPR', 'VIEW');

        $emailOrUsername = trim((string) $request->request->get('user_identifier'));
        if (empty($emailOrUsername)) {
            return new JsonResponse(['error' => 'User identifier is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneByEmailOrUsername($emailOrUsername);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        // Aggregate user personal data (Profile, Comments, Activity, Logins)
        $userData = [
            'export_date' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'user' => [
                'id' => $user->getId()?->toString(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
            // Add other associated entity data here (e.g. comments, posts, sessions)
        ];

        $response = new JsonResponse($userData);
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="sar_export_%s.json"', $user->getUsername()));

        return $response;
    }

    /**
     * Handles Right to be Forgotten / User Anonymization
     */
    #[Route('/incc/security/privacy/anonymize-user', name: 'incc_security_privacy_anonymize_user', methods: ['POST'])]
    public function anonymizeUser(Request $request): Response
    {
        $this->denyAccessUnlessGranted('PRIVACY_GDPR', 'DELETE');

        if (!$this->isCsrfTokenValid('anonymize_user', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('incc_security_privacy');
        }

        $emailOrUsername = trim((string) $request->request->get('user_identifier'));
        $user = $this->userRepository->findOneByEmailOrUsername($emailOrUsername);

        if (!$user) {
            $this->addFlash('error', sprintf('User "%s" not found.', $emailOrUsername));
            return $this->redirectToRoute('incc_security_privacy');
        }

        // Perform anonymization (Scramble email, username, display name)
        $anonymizedHash = substr(md5(uniqid((string) rand(), true)), 0, 10);
        $user->setEmail(sprintf('deleted_%s@anonymized.invalid', $anonymizedHash));
        $user->setUsername(sprintf('deleted_user_%s', $anonymizedHash));
        $user->setDisplayName('Anonymized User');
        $user->setDisabled(true);

        $this->userRepository->save($user);

        $this->addFlash('success', sprintf('User "%s" has been anonymized.', $emailOrUsername));

        return $this->redirectToRoute('incc_security_privacy');
    }

    /**
     * Helper to load privacy settings into an associative array
     */
    private function getPrivacySettings(): array
    {
        $keys = [
            'banner_enabled' => '1',
            'consent_mode' => 'opt_in',
            'banner_message' => 'We use cookies to improve your browsing experience and analyze site traffic.',
            'anonymize_ips' => '1',
            'log_retention_days' => '90',
        ];

        $settings = [];
        foreach ($keys as $key => $default) {
            $settingName = self::SETTING_PREFIX . $key;
            $setting = $this->settingRepository->findOneByName($settingName);

            $settings[$key] = $setting?->getValue() ?? $default;
        }

        return $settings;
    }
}