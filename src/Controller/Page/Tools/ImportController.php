<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Service\Import\Category\CategoryImportService;
use Inachis\Service\Import\Category\CategoryImportValidator;
use Inachis\Service\Import\ImportDetector;
use Inachis\Service\Import\Page\PageImportMapper;
use Inachis\Service\Import\Page\PageImportService;
use Inachis\Service\Import\Page\PageImportValidator;
use Inachis\Service\Import\Parser\FileParserRegistry;
use Inachis\Service\Import\Series\SeriesImportService;
use Inachis\Service\Import\Series\SeriesImportValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportController extends AbstractInachisController
{
    #[Route('/incp/tools/import', name: 'incp_tools_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        FileParserRegistry $parserRegistry,
        ImportDetector $detector,
        CategoryImportService $categoryImportService,
        CategoryImportValidator $categoryValidator,
        PageImportMapper $pageMapper,
        PageImportValidator $pageValidator,
        SeriesImportService $seriesImportService,
        SeriesImportValidator $seriesValidator,
    ): Response {
        $this->viewModel->page->title = 'Import Content';
        $this->viewModel->page->tab = 'import';

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('import_file');

            if (!$file instanceof UploadedFile || !$file->isValid()) {
                $this->addFlash('error', 'Please upload a valid import file.');
                return $this->redirectToRoute('incp_tools_import');
            }

            try {
                $parser = $parserRegistry->getParserForExtension($file->getClientOriginalExtension());
                $parsedData = $parser->parse($file->getPathname());
                $importType = $detector->detectImportType($parsedData);

                [$dtos, $warnings] = match ($importType) {
                    'category' => (function() use ($categoryImportService, $categoryValidator, $parsedData) {
                        $dtos = $categoryImportService->mapToDto($parsedData);
                        return [$dtos, $categoryValidator->validateAll($dtos)];
                    })(),
                    'series' => (function() use ($seriesImportService, $seriesValidator, $parsedData) {
                        $dtos = $seriesImportService->mapToDto($parsedData);
                        return [$dtos, $seriesValidator->validateAll($dtos)];
                    })(),
                    default => (function() use ($pageMapper, $pageValidator, $parsedData) {
                        $dtos = $pageMapper->mapToDto($parsedData);
                        return [$dtos, $pageValidator->validateAll($dtos)];
                    })(),
                };

                // Store payload in session for process phase
                $session = $request->getSession();
                $session->set('import_payload', [
                    'type' => $importType,
                    'dtos' => serialize($dtos),
                ]);

                return $this->render('inadmin/page/tools/import_preview.html.twig', [
                    'viewModel' => $this->viewModel,
                    'import_type' => $importType,
                    'items' => $dtos,
                    'warnings' => $warnings,
                ]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Import file parsing error: ' . $e->getMessage());
                return $this->redirectToRoute('incp_tools_import');
            }
        }

        return $this->render('inadmin/page/tools/import_upload.html.twig', [
            'viewModel' => $this->viewModel,
        ]);
    }

    #[Route('/incp/tools/import/process', name: 'incp_tools_import_process', methods: ['POST'])]
    public function process(
        Request $request,
        CategoryImportService $categoryImportService,
        PageImportService $pageImportService,
        SeriesImportService $seriesImportService,
    ): Response {
        $session = $request->getSession();
        $payload = $session->get('import_payload');

        if (!$payload || !isset($payload['type'], $payload['dtos'])) {
            $this->addFlash('error', 'Import session expired or empty data.');
            return $this->redirectToRoute('incp_tools_import');
        }

        $importType = $payload['type'];
        $dtos = unserialize($payload['dtos']);
        $user = $this->getUser();

        $options = new ImportOptionsDto();
        $options->createMissingCategories = (bool) $request->request->get('createMissingCategories', true);
        $options->createMissingTags = (bool) $request->request->get('createMissingTags', true);

        $result = match ($importType) {
            'category' => $categoryImportService->import($dtos),
            'series' => $seriesImportService->import($dtos, $user, $options),
            default => $pageImportService->import($dtos, $user, $options),
        };

        $session->remove('import_payload');

        if (!empty($result->warnings)) {
            foreach ($result->warnings as $warning) {
                $this->addFlash('warning', $warning);
            }
        }

        $importedCount = match ($importType) {
            'category' => $result->categoriesCreated + $result->categoriesUpdated,
            'series' => $result->seriesImported,
            default => $result->pagesImported,
        };

        $this->addFlash('success', sprintf('Successfully processed %d %s item(s).', $importedCount, $importType));

        return $this->redirectToRoute('incp_tools_import');
    }
}
