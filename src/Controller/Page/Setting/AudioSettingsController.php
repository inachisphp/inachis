<?php

declare(strict_types=1);

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Ai\AiAudioManager;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AudioSettingsController extends AbstractInachisController
{
    #[Route('/incp/settings/audio', name: 'incp_settings_audio', methods: ['GET', 'POST'])]
    public function index(Request $request, AiAudioManager $audioManager): Response
    {
        $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/';

        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        // --- HANDLE POST ACTIONS ---
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'upload') {
                $this->handleUpload($request, $uploadsDir, 'stinger_file', 'pod_stinger.mp3');
                $this->handleUpload($request, $uploadsDir, 'trailer_file', 'pod_trailer.mp3');
            } elseif ($action === 'generate_stinger' || $action === 'generate_trailer') {
                $isStinger = ($action === 'generate_stinger');
                $scriptText = trim((string) $request->request->get($isStinger ? 'stinger_script' : 'trailer_script'));
                $voice = trim((string) $request->request->get($isStinger ? 'stinger_voice' : 'trailer_voice', 'alloy'));
                $targetFile = $uploadsDir . ($isStinger ? 'pod_stinger.mp3' : 'pod_trailer.mp3');

                if (empty($scriptText)) {
                    $this->addFlash('error', 'Please enter a script to generate audio.');
                } elseif (!$audioManager->isConfigured()) {
                    $this->addFlash('error', 'The active AI provider is not configured with an API key.');
                } else {
                    try {
                        $provider = $audioManager->getActiveProvider();
                        $mp3Binary = $provider->generateSpeech($scriptText, $voice);
                        file_put_contents($targetFile, $mp3Binary);

                        $this->addFlash('success', sprintf('Successfully generated %s using AI!', $isStinger ? 'Stinger' : 'Trailer'));
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'AI Generation failed: ' . $e->getMessage());
                    }
                }
            } elseif ($action === 'remove_stinger') {
                $this->removeFile($uploadsDir . 'pod_stinger.mp3');
            } elseif ($action === 'remove_trailer') {
                $this->removeFile($uploadsDir . 'pod_trailer.mp3');
            }

            return $this->redirectToRoute('incp_settings_audio');
        }

        $stingerPath = $uploadsDir . 'pod_stinger.mp3';
        $trailerPath = $uploadsDir . 'pod_trailer.mp3';

        $this->viewModel->page->title = 'Audio Bumpers & Settings';
        $this->viewModel->page->tab = 'settings';

        return $this->render('inadmin/page/settings/audio.html.twig', [
            'viewModel' => $this->viewModel,
            'aiConfigured' => $audioManager->isConfigured(),
            'activeProvider' => $audioManager->getActiveProvider()?->getName() ?? 'none',
            'stinger' => file_exists($stingerPath) ? [
                'exists' => true,
                'size' => filesize($stingerPath),
                'mtime' => filemtime($stingerPath),
            ] : ['exists' => false],
            'trailer' => file_exists($trailerPath) ? [
                'exists' => true,
                'size' => filesize($trailerPath),
                'mtime' => filemtime($trailerPath),
            ] : ['exists' => false],
        ]);
    }

    private function handleUpload(Request $request, string $uploadsDir, string $inputName, string $targetFilename): void
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get($inputName);
        if ($file && $file->isValid()) {
            if ($file->getMimeType() !== 'audio/mpeg' && $file->getClientOriginalExtension() !== 'mp3') {
                $this->addFlash('error', 'Only MP3 files are permitted.');
                return;
            }

            try {
                $file->move($uploadsDir, $targetFilename);
                $this->addFlash('success', sprintf('Updated %s successfully.', $targetFilename));
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload audio file.');
            }
        }
    }

    private function removeFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
            $this->addFlash('success', 'Audio file removed successfully.');
        }
    }
}
