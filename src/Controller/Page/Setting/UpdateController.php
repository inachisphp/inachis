<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
use Inachis\Updater\ReleaseRollback;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UpdateController extends AbstractInachisController
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly GithubReleaseProvider $releaseProvider,
        private readonly UpdatePlanner $updatePlanner,
        private readonly ReleaseInstaller $releaseInstaller,
        private readonly ReleaseCleaner $releaseCleaner,
        private readonly ReleaseLocator $releaseLocator,
        private readonly ReleaseRollback $releaseRollback,
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
     * Display update status, current version, latest version, and rollback targets.
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
            // System is up to date
        } catch (IncompatibleVersionException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = sprintf('Unable to check for updates: %s', $e->getMessage());
        }

        // Get available previous releases for rollback
        $availableRollbacks = $this->releaseRollback->availableRollbacks();
        $rollbackCandidate = $availableRollbacks[0] ?? null;

        return $this->render('inadmin/page/settings/update.html.twig', [
            'viewModel' => $this->viewModel,
            'pageTitle' => 'System Update',
            'currentVersion' => $currentVersion,
            'latestManifest' => $latestManifest,
            'updatePlan' => $updatePlan,
            'rollbackCandidate' => $rollbackCandidate,
            'olderRollbacks' => array_slice($availableRollbacks, 1),
            'error' => $error,
            'isUpToDate' => null === $updatePlan && null === $error,
        ]);
    }

    /**
     * Download and install the latest core update.
     */
    #[Route('/incp/settings/updater/apply', name: 'incp_settings_update_apply', methods: ['POST'])]
    public function apply(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('system_update', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('incp_settings_update');
        }

        $currentVersion = $this->versionService->getVersion();

        try {
            $manifest = $this->releaseProvider->latest();
            $plan = $this->updatePlanner->plan($currentVersion, $manifest);

            $tempArchive = sys_get_temp_dir().DIRECTORY_SEPARATOR.$plan->package;
            $this->releaseProvider->download($manifest, $tempArchive);

            $sharedDir = $this->releaseLocator->sharedDirectory();
            $sharedMappings = [
                '.env' => $sharedDir.'/.env',
                '.env.local.php' => $sharedDir.'/.env.local.php',
                'public/imgs' => $sharedDir.'/public/imgs',
                'var' => $sharedDir.'/var',
                'public/maintenance.html' => $sharedDir.'/public/maintenance.html',
            ];

            $this->releaseInstaller->install($manifest, $tempArchive, $sharedMappings);
            $this->releaseCleaner->prune(keep: 3);

            if (file_exists($tempArchive)) {
                unlink($tempArchive);
            }

            $this->addFlash('success', sprintf('Successfully updated Inachis to v%s!', $plan->targetVersion));
        } catch (\Throwable $exception) {
            $this->addFlash('danger', sprintf('Update failed: %s', $exception->getMessage()));
        }

        return $this->redirectToRoute('incp_settings_update');
    }

    /**
     * Atomically roll back to the previous release.
     */
    #[Route('/incp/settings/updater/rollback', name: 'incp_settings_update_rollback', methods: ['POST'])]
    public function rollback(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('system_rollback', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid security token. Please try again.');

            return $this->redirectToRoute('incp_settings_update');
        }

        try {
            $revertedRelease = $this->releaseRollback->rollback();
            $this->addFlash('success', sprintf('Successfully rolled back to v%s!', $revertedRelease->version));
        } catch (\Throwable $exception) {
            $this->addFlash('danger', sprintf('Rollback failed: %s', $exception->getMessage()));
        }

        return $this->redirectToRoute('incp_settings_update');
    }
}
