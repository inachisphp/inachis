<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\User;
use Inachis\Model\CategoryExportDto;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Service\Import\Category\CategoryImportService;
use Inachis\Service\Import\Category\CategoryImportValidator;
use Inachis\Service\Import\ImportDetector;
use Inachis\Service\Import\Page\PageImportService;
use Inachis\Service\Import\Page\PageImportValidator;
use Inachis\Service\Import\Series\SeriesImportService;
use Inachis\Service\Import\Series\SeriesImportValidator;
use Inachis\Service\Parser\MarkdownFileParser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for importing pages and posts.
 */
class ImportController extends AbstractInachisController
{
    #[Route('/incp/tools/import', name: 'incp_tools_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        ImportDetector $importDetector,
        CategoryImportService $categoryImportService,
        CategoryImportValidator $categoryImportValidator,
        SeriesImportService $seriesImportService,
        SeriesImportValidator $seriesImportValidator,
        PageImportService $pageImportService,
        PageImportValidator $pageImportValidator,
    ): Response {
        $this->viewModel->page->title = 'Import';
        $this->viewModel->page->tab = 'import';

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('import_file');

            if (!$uploadedFile) {
                $this->addFlash('error', 'No file uploaded.');

                return $this->redirectToRoute('incp_tools_import');
            }

            $content = file_get_contents($uploadedFile->getPathname());
            if (false === $content) {
                $this->addFlash('error', 'Failed to read uploaded file.');

                return $this->redirectToRoute('incp_tools_import');
            }

            $ext = strtolower($uploadedFile->getClientOriginalExtension());

            try {
                /** @var list<mixed> $data */
                $data = match ($ext) {
                    'json' => (array) json_decode($content, true, 512, JSON_THROW_ON_ERROR),
                    'xml' => (function () use ($content): array {
                        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
                        $array = json_decode((string) json_encode($xml), true);

                        return is_array($array) && isset($array['category']) && is_array($array['category'])
                            ? $array['category']
                            : [];
                    })(),
                    'md' => (function () use ($content): array {
                        $parser = new MarkdownFileParser($this->entityManager);
                        $page = $parser->parse($content);

                        return [[
                            'title' => $page->getTitle(),
                            'content' => $page->getContent(),
                            'status' => $page->getStatus(),
                            'postDate' => $page->getPostDate()->format('Y-m-d H:i:s'),
                        ]];
                    })(),
                    default => throw new \InvalidArgumentException('Unsupported file format.'),
                };
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Error parsing file: '.$e->getMessage());

                return $this->redirectToRoute('incp_tools_import');
            }

            $importType = $importDetector->detectImportType($data);

            switch ($importType) {
                case 'page':
                case 'post':
                    /** @var list<array<string, mixed>> $pageData */
                    $pageData = $data;
                    /** @var list<PageExportDto> $dtos */
                    $dtos = array_map(
                        static function (array $item): PageExportDto {
                            $dto = new PageExportDto();
                            $dto->title = is_scalar($item['title'] ?? null) ? (string) $item['title'] : '';
                            $dto->content = is_scalar($item['content'] ?? null) ? (string) $item['content'] : '';
                            $dto->subTitle = is_scalar($item['subTitle'] ?? null) ? (string) $item['subTitle'] : null;
                            $dto->type = is_scalar($item['type'] ?? null) ? (string) $item['type'] : 'post';
                            $dto->status = is_scalar($item['status'] ?? null) ? (string) $item['status'] : 'draft';
                            $dto->postDate = is_scalar($item['postDate'] ?? null) ? (string) $item['postDate'] : null;
                            $dto->visible = (bool) ($item['visible'] ?? true);

                            return $dto;
                        },
                        $pageData,
                    );
                    $warnings = $pageImportValidator->validateAll($dtos);
                    break;

                case 'series':
                    /** @var list<array{title?: string, subTitle?: string, url?: string, description?: string, firstDate?: string, lastDate?: string, visible?: bool, items?: list<string>}> $seriesData */
                    $seriesData = $data;
                    $dtos = $seriesImportService->mapToDto($seriesData);
                    $warnings = $seriesImportValidator->validateAll($dtos);
                    break;

                case 'category':
                    /** @var list<array{id?: string|null, title?: string|null, fullPath?: string|null, description?: string|null, visible?: bool|null, image?: string|null, icon?: string|null}> $categoryData */
                    $categoryData = $data;
                    $dtos = $categoryImportService->mapToDto($categoryData);
                    $warnings = $categoryImportValidator->validateAll($dtos);
                    break;

                default:
                    $this->addFlash('error', 'Unknown import type.');

                    return $this->redirectToRoute('incp_tools_import');
            }

            $request->getSession()->set('import_preview', [
                'type' => $importType,
                'items' => $dtos,
                'warnings' => $warnings,
            ]);

            return $this->render('inadmin/page/tools/import_preview.html.twig', [
                'viewModel' => $this->viewModel,
                'items' => $dtos,
            ]);
        }

        return $this->render('inadmin/page/tools/import_upload.html.twig', [
            'viewModel' => $this->viewModel,
        ]);
    }

    #[Route('/incp/tools/import/execute', name: 'incp_tools_import_process', methods: ['POST'])]
    public function importExecute(
        Request $request,
        CategoryImportService $categoryImportService,
        PageImportService $pageImportService,
        SeriesImportService $seriesImportService,
    ): Response {
        $session = $request->getSession();
        /** @var array{type: string, items: list<mixed>}|null $importPreview */
        $importPreview = $session->get('import_preview');

        if (!is_array($importPreview) || empty($importPreview['items'])) {
            $this->addFlash('error', 'No items to import.');

            return $this->redirectToRoute('incp_tools_import');
        }

        $importType = $importPreview['type'];
        $dtos = $importPreview['items'];

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $options = new ImportOptionsDto();
        $options->createMissingCategories = $request->request->getBoolean('createMissingCategories');
        $options->createMissingTags = $request->request->getBoolean('createMissingTags');

        $warnings = [];
        $resultSummary = [];

        switch ($importType) {
            case 'page':
            case 'post':
                /** @var list<PageExportDto> $pageDtos */
                $pageDtos = $dtos;
                $result = $pageImportService->import($pageDtos, $currentUser, $options);
                $warnings = $result->warnings;
                $resultSummary = [
                    'items' => $result->pagesImported,
                    'categories' => $result->categoriesCreated,
                    'tags' => $result->tagsCreated,
                    'message' => sprintf(
                        'Imported %d pages, created %d categories, and %d tags.',
                        $result->pagesImported,
                        $result->categoriesCreated,
                        $result->tagsCreated,
                    ),
                ];
                break;

            case 'series':
                /** @var list<SeriesExportDto> $seriesDtos */
                $seriesDtos = $dtos;
                $result = $seriesImportService->import($seriesDtos);
                $warnings = $result->warnings;
                $resultSummary = [
                    'items' => $result->seriesImported,
                    'pagesLinked' => $result->pagesLinked,
                    'message' => sprintf(
                        'Imported %d series, and linked %d pages.',
                        $result->seriesImported,
                        $result->pagesLinked,
                    ),
                ];
                break;

            case 'category':
                /** @var list<CategoryExportDto> $categoryDtos */
                $categoryDtos = $dtos;
                $result = $categoryImportService->import($categoryDtos);
                $warnings = $result->warnings;
                $resultSummary = [
                    'items' => $result->categoriesCreated,
                    'message' => sprintf(
                        'Imported %d categories, and updated %d categories.',
                        $result->categoriesCreated,
                        $result->categoriesUpdated,
                    ),
                ];
                break;

            default:
                $this->addFlash('error', 'Invalid import type.');

                return $this->redirectToRoute('incp_tools_import');
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

        return $this->redirectToRoute('incp_tools_index');
    }
}
