<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Message\CreateBackupMessage;
use Inachis\Message\RestoreBackupMessage;
use Inachis\Service\Formatting\NumberFormatter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class BackupController extends AbstractInachisController
{
    /**
     * List all generated backups in var/backups.
     */
    #[Route('/incp/tools/backups', name: 'incp_tools_backups', methods: ['GET'])]
    public function index(
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ): Response
    {
        $backupDir = $this->getBackupDirectory($projectDir);
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*.sql.gz');
            if ($files !== false) {
                foreach ($files as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => NumberFormatter::formatBytes((int) filesize($file)),
                        'createdAt' => (new \DateTimeImmutable())->setTimestamp(filemtime($file)),
                    ];
                }

                // Sort newest backups first
                usort($backups, fn (array $a, array $b) => $b['createdAt'] <=> $a['createdAt']);
            }
        }

        $this->viewModel->page->title = 'Backups';
        $this->viewModel->page->tab = 'tools';

        return $this->render('inadmin/page/tools/backups.html.twig', [
            'viewModel' => $this->viewModel,
            'backups' => $backups,
        ]);
    }

    /**
     * Trigger backup creation via Symfony Messenger.
     */
    #[Route('/incp/tools/backups/create', name: 'incp_tools_backups_create', methods: ['POST'])]
    public function create(
        Request $request,
        MessageBusInterface $bus,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ): Response {
        if (!$this->isCsrfTokenValid('backup_create', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('incp_tools_backups');
        }

        $bus->dispatch(new CreateBackupMessage(
            outputDir: $this->getBackupDirectory($projectDir),
            requestedBy: $this->getUser()?->getUserIdentifier()
        ));

        $this->addFlash('success', 'Backup creation process started.');

        return $this->redirectToRoute('incp_tools_backups');
    }

    /**
     * Download a specific backup file safely using BinaryFileResponse.
     */
    #[Route('/incp/tools/backups/download/{filename}', name: 'incp_tools_backups_download', methods: ['GET'])]
    public function download(
        string $filename,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ): Response {
        // Enforce basename to prevent directory traversal attacks
        $safeFilename = basename($filename);
        $filePath = $this->getBackupDirectory($projectDir) . '/' . $safeFilename;

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Requested backup file does not exist.');
            return $this->redirectToRoute('incp_tools_backups');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $safeFilename
        );

        return $response;
    }

    /**
     * Restore database from an existing server backup or an uploaded .sql.gz file.
     */
    #[Route('/incp/tools/backups/restore', name: 'incp_tools_backups_restore', methods: ['POST'])]
    public function restore(
        Request $request,
        MessageBusInterface $bus,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ): Response {
        if (!$this->isCsrfTokenValid('backup_restore', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $filename = $request->request->get('filename');
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('backup_file');
        $restorePath = null;

        if ($uploadedFile instanceof UploadedFile) {
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            if (!in_array($extension, ['gz', 'sql'], true)) {
                return new JsonResponse(['error' => 'Invalid file extension. Please upload a .sql.gz file.'], Response::HTTP_BAD_REQUEST);
            }

            $newFilename = sprintf('uploaded_%s.sql.gz', (new \DateTimeImmutable())->format('Y-m-d_His'));
            $uploadedFile->move($this->getBackupDirectory($projectDir), $newFilename);
            $restorePath = $this->getBackupDirectory($projectDir) . '/' . $newFilename;
        } elseif (is_string($filename) && $filename !== '') {
            $restorePath = $this->getBackupDirectory($projectDir) . '/' . basename($filename);
        }

        if ($restorePath === null || !file_exists($restorePath)) {
            return new JsonResponse(['error' => 'No valid backup file selected for restore.'], Response::HTTP_BAD_REQUEST);
        }

        $jobId = bin2hex(random_bytes(16));

        $bus->dispatch(new RestoreBackupMessage(
            filePath: $restorePath,
            jobId: $jobId,
            requestedBy: $this->getUser()?->getUserIdentifier()
        ));

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'jobId' => $jobId,
                'message' => 'Restore initiated successfully.',
            ]);
        }

        $this->addFlash('success', 'Database restore task queued successfully.');
        return $this->redirectToRoute('incp_tools_backups');
    }

    /**
     * Delete an existing backup file.
     */
    #[Route('/incp/tools/backups/delete/{filename}', name: 'incp_tools_backups_delete', methods: ['POST'])]
    public function delete(
        string $filename,
        Request $request,
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir,
    ): Response {
        $safeFilename = basename($filename);

        if (!$this->isCsrfTokenValid('backup_delete_' . $safeFilename, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('incp_tools_backups');
        }

        $filePath = $this->getBackupDirectory($projectDir) . '/' . $safeFilename;

        if (file_exists($filePath) && unlink($filePath)) {
            $this->addFlash('success', sprintf('Backup "%s" was successfully deleted.', $safeFilename));
        } else {
            $this->addFlash('error', 'Failed to delete the specified backup file.');
        }

        return $this->redirectToRoute('incp_tools_backups');
    }

    private function getBackupDirectory(string $projectDir): string
    {
        return $projectDir . '/var/backups';
    }
}
