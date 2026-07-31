<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Exception\Updater\IncompatibleVersionException;
use Inachis\Exception\Updater\NoUpdateAvailableException;
use Inachis\Factory\PageViewFactory;
use Inachis\Repository\Waste\WasteRepository;
use Inachis\Service\System\VersionService;
use Inachis\Updater\Downloader\Downloader;
use Inachis\Updater\Planner\UpdatePlanner;
use Inachis\Updater\Provider\GithubReleaseProvider;
use Inachis\Updater\ReleaseCleaner;
use Inachis\Updater\ReleaseInstaller;
use Inachis\Updater\ReleaseLocator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final class UpdateController extends AbstractInachisController
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly GithubReleaseProvider $releaseProvider,
        private readonly UpdatePlanner $updatePlanner,
        private readonly ReleaseInstaller $releaseInstaller,
        private readonly ReleaseCleaner $releaseCleaner,
        private readonly ReleaseLocator $releaseLocator,
        private readonly Downloader $downloader,
        protected EntityManagerInterface $entityManager,
        protected ParameterBagInterface $params,
        protected Security $security,
        protected TranslatorInterface $translator,
        protected WasteRepository $wasteRepository,
        PageViewFactory $pageViewFactory,
        protected RequestStack $requestStack,
    ) {
        parent::__construct($entityManager, $params, $security, $translator, $wasteRepository, $pageViewFactory, $requestStack);
    }

    /**
     * Display update status, current version, latest version, and release notes.
     */
    #[Route('/incp/settings/updater', name: 'incp_settings_update', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $currentVersion = $this->versionService->getVersion();
        $latestManifest = null;
        $updatePlan = null;
        $error = null;

        try {
            $latestManifest = $this->releaseProvider->latest();
            $updatePlan = $this->updatePlanner->plan($currentVersion, $latestManifest);
        } catch (NoUpdateAvailableException) {
            // System is up to date — not an error condition
        } catch (IncompatibleVersionException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = sprintf('Unable to check for updates: %s', $e->getMessage());
        }

        return $this->render('inadmin/page/settings/update.html.twig', [
            'viewModel' => $this->viewModel,
            'pageTitle' => 'System Update',
            'currentVersion' => $currentVersion,
            'latestManifest' => $latestManifest,
            'updatePlan' => $updatePlan,
            'error' => $error,
            'isUpToDate' => $updatePlan === null && $error === null,
        ]);
    }

    /**
     * Download and install the latest core update.
     */
    #[Route('/incp/settings/updater/apply', name: 'incp_settings_update_apply', methods: ['POST'])]
    public function apply(Request $request): Response
    {
        // CSRF verification check
        if (!$this->isCsrfTokenValid('system_update', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('incp_settings_update');
        }

        $currentVersion = $this->versionService->getVersion();

        try {
            $manifest = $this->releaseProvider->latest();
            $plan = $this->updatePlanner->plan($currentVersion, $manifest);

            // 1. Download zip archive to temporary location
            $tempArchive = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $plan->package;
            $this->releaseProvider->download($manifest, $tempArchive);

            // 2. Define shared file & directory mappings (mirroring your manual symlink steps)
            $sharedDir = $this->releaseLocator->sharedDirectory();
            $sharedMappings = [
                '.env'                   => $sharedDir . '/.env',
                '.env.local.php'         => $sharedDir . '/.env.local.php',
                'public/imgs'            => $sharedDir . '/public/imgs',
                'var'                    => $sharedDir . '/var',
                'public/maintenance.html' => $sharedDir . '/public/maintenance.html',
            ];

            // 3. Install, execute Doctrine migrations, and atomically swap symlinks
            $this->releaseInstaller->install($manifest, $tempArchive, $sharedMappings);

            // 4. Prune older release folders (keeping current + 2 rollback releases)
            $this->releaseCleaner->prune(keep: 3);

            // Clean up temporary download file
            if (file_exists($tempArchive)) {
                unlink($tempArchive);
            }

            $this->addFlash('success', sprintf('Successfully updated Inachis to v%s!', $plan->targetVersion));

        } catch (Throwable $exception) {
            $this->addFlash('danger', sprintf('Update failed: %s', $exception->getMessage()));
        }

        return $this->redirectToRoute('incp_settings_update');
    }
}
