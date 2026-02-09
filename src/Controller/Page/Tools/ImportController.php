<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\{CategoryPathDto,PageExportDto,TagDto};
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Model\CategoryExportDto;
use Inachis\Service\Import\ImportDetector;
use Inachis\Service\Import\Series\SeriesImportService;
use Inachis\Service\Import\Series\SeriesImportValidator;
use Inachis\Service\Import\Category\CategoryImportService;
use Inachis\Service\Import\Category\CategoryImportValidator;
use Inachis\Service\Page\Import\PageImportService;
use Inachis\Service\Page\Import\PageImportValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for importing pages and posts
 */
#[IsGranted('ROLE_ADMIN')]
class ImportController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param PageImportService $pageImportService
     * @param PageImportValidator $pageImportValidator
     * @return Response
     */
    #[Route('/incc/tools/import', name: 'incc_tools_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        ImportDetector $importDetector,
        CategoryImportService $categoryImportService,
        CategoryImportValidator $categoryImportValidator,
        SeriesImportService $seriesImportService,
        SeriesImportValidator $seriesImportValidator,
        PageImportService $pageImportService,
        PageImportValidator $pageImportValidator
    ): Response {
        $this->data['page']['title'] = 'Import';
        $this->data['page']['tab'] = 'import';

        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('import_file');

            if (!$uploadedFile) {
                $this->addFlash('error', 'No file uploaded.');
                return $this->redirectToRoute('incc_tools_import');
            }

            $content = file_get_contents($uploadedFile->getPathname());
            $ext = strtolower($uploadedFile->getClientOriginalExtension());
            $importType = 'page';

            try {
                switch ($ext) {
                    case 'json':
                        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                        break;
                    case 'xml':
                        $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
                        $data = json_decode(json_encode($xml), true)['category'];
                        break;
                    case 'md':
                        throw new \InvalidArgumentException('Markdown import not implemented yet.');
                    default:
                        throw new \InvalidArgumentException('Unsupported file format.');
                }
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Error parsing file: ' . $e->getMessage());
                return $this->redirectToRoute('incc_tools_import');
            }

            $importType = $importDetector->detectImportType($data);

            switch ($importType) {
                case 'page':
                    $dtos = $pageImportService->mapToDto($data);
                    $warnings = $pageImportValidator->validateAll($dtos);
                    break;

                case 'series':
                    $dtos = $seriesImportService->mapToDto($data);
                    $warnings = $seriesImportValidator->validateAll($dtos);
                    break;

                case 'category':
                    $dtos = $categoryImportService->mapToDto($data);
                    $warnings = $categoryImportValidator->validateAll($dtos);
                    break;

                default:
                    $this->addFlash('error', 'Unknown import type.');
                    return $this->redirectToRoute('incc_tools_import');
            }

            $request->getSession()->set('import_preview', [
                'type' => $importType,
                'items' => $dtos,
                'warnings' => $warnings,
            ]);
            $this->data['items'] = $dtos;
            $this->data['warnings'] = $warnings;
            $this->data['import_type'] = $importType;
            return $this->render('inadmin/page/tools/import_preview.html.twig', $this->data);
        }

        return $this->render('inadmin/page/tools/import_upload.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param PageImportService $pageImportService
     * @return Response
     */
    #[Route('/incc/tools/import/execute', name: 'incc_tools_import_process', methods: ['POST'])]
    public function importExecute(
        Request $request,
        CategoryImportService $categoryImportService,
        PageImportService $pageImportService,
        SeriesImportService $seriesImportService,
    ): Response {
        $session = $request->getSession();
        $importPreview = $session->get('import_preview');

        if (!$importPreview || empty($importPreview['items'])) {
            $this->addFlash('error', 'No items to import.');
            return $this->redirectToRoute('incc_tools_import');
        }

        $importType = $importPreview['type'];
        $dtos = $importPreview['items'];

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $options = new ImportOptionsDto();
        $options->createMissingCategories = $request->request->getBoolean('createMissingCategories', false);
        $options->createMissingTags = $request->request->getBoolean('createMissingTags', false);
        $options->overridePostDates = $request->request->getBoolean('overridePostDates', false);

        $warnings = [];
        $resultSummary = [];

        switch ($importType) {
            case 'page':
                $result = $pageImportService->import($dtos, $currentUser, $options);
                $warnings = $result->warnings;
                $resultSummary = [
                    'items' => $result->pagesImported,
                    'categories' => $result->categoriesCreated,
                    'tags' => $result->tagsCreated,
                    'message' => sprintf(
                        'Imported %d pages, created %d categories, and %d tags.',
                        $result->pagesImported,
                        $result->categoriesCreated,
                        $result->tagsCreated
                    ),
                ];
                break;

            case 'series':
                $result = $seriesImportService->import($dtos, $currentUser, $options);
                $warnings = $result->warnings;
                $resultSummary = [
                    'items' => $result->seriesImported ?? 0,
                    'pagesLinked' => $result->pagesLinked ?? 0,
                    'message' => sprintf(
                        'Imported %d series and linked %d pages.',
                        $result->seriesImported,
                        $result->pagesLinked
                    ),
                ];
                break;

            case 'category':
                $result = $categoryImportService->import($dtos, $currentUser, $options);
                $warnings = $result->warnings ?? [];
                $resultSummary = [
                    'items' => $result->categoriesCreated ?? 0,
                    'message' => sprintf(
                        'Imported %d categories and updated %d categories.',
                        $result->categoriesCreated,
                        $result->categoriesUpdated
                    ),
                ];
                break;

            default:
                $this->addFlash('error', 'Invalid import type.');
                return $this->redirectToRoute('incc_tools_import');
        }

        if ($resultSummary['items'] > 0) {
            $this->addFlash('success', $resultSummary['message']);
        } else {
            $this->addFlash('error', 'No items imported.');
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->addFlash('warning', $warning);
            }
        }

        $session->remove('import_preview');

        return $this->redirectToRoute('incc_tools_index');
    }
}
