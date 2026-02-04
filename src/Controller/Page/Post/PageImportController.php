<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User;
use Inachis\Model\Import\ImportOptionsDto;
use Inachis\Model\Page\PageExportDto;
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
class PageImportController extends AbstractInachisController
{
    /**
     * Step 1: Upload file and show preview
     */
    #[Route('/incc/import', name: 'incc_post_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        PageImportService $pageImportService,
        PageImportValidator $pageImportValidator
    ): Response {
        $this->data['page']['title'] = 'Import Pages and Posts';
        $this->data['page']['tab'] = 'import';

        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('import_file');

            if (!$uploadedFile) {
                $this->addFlash('error', 'No file uploaded.');
                return $this->redirectToRoute('incc_post_import');
            }

            $content = file_get_contents($uploadedFile->getPathname());
            $ext = strtolower($uploadedFile->getClientOriginalExtension());

            try {
                if ($ext === 'json') {
                    $pages = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                } elseif ($ext === 'xml') {
                    $xml = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);
                    $pages = json_decode(json_encode($xml), true);
                } elseif ($ext === 'md') {
                    // @todo implement markdown import based on previous version
                    throw new \InvalidArgumentException('Markdown import not implemented yet.');
                } else {
                    throw new \InvalidArgumentException('Unsupported file format.');
                }
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Error parsing file: ' . $e->getMessage());
                return $this->redirectToRoute('incc_post_import');
            }

            $pageDtos = [];
            $warnings = [];
            foreach ($pages as $page) {
                $dto = new PageExportDto();
                $dto->title = $page['title'] ?? '';
                $dto->subTitle = $page['subTitle'] ?? null;
                $dto->content = $page['content'] ?? null;
                $dto->type = $page['type'] ?? 'post';
                $dto->status = $page['status'] ?? 'draft';
                $dto->visibility = $page['visibility'] ?? true;
                $dto->allowComments = $page['allowComments'] ?? false;
                $dto->language = $page['language'] ?? null;
                $dto->timezone = $page['timezone'] ?? null;
                $dto->postDate = $page['postDate'] ?? null;

                $dto->categories = [];
                foreach ($page['categories'] ?? [] as $cat) {
                    $catDto = new \Inachis\Model\Page\CategoryPathDto();
                    $catDto->path = $cat['path'] ?? '';
                    $dto->categories[] = $catDto;
                }

                $dto->tags = [];
                foreach ($page['tags'] ?? [] as $tag) {
                    $tagDto = new \Inachis\Model\Page\TagDto();
                    $tagDto->title = $tag['title'] ?? '';
                    $dto->tags[] = $tagDto;
                }

                $pageDtos[] = $dto;
            }

            $pageImportValidator->validateAll($pageDtos);
            foreach ($warnings as $warning) {
                $this->addFlash('warning', $warning);
            }

            $request->getSession()->set('page_import_preview', [
                'pages' => $pageDtos,
                'warnings' => $warnings
            ]);
            $this->data['pages'] = $pageDtos;
            $this->data['warnings'] = $warnings;
            return $this->render('inadmin/page/post/import_preview.html.twig', $this->data);
        }

        return $this->render('inadmin/page/post/import_upload.html.twig', $this->data);
    }

    /**
     * Step 2: Execute import
     */
    #[Route('/incc/import/execute', name: 'incc_post_process', methods: ['POST'])]
    public function importExecute(
        Request $request,
        PageImportService $pageImportService,
    ): Response {
        $session = $request->getSession();
        $pageDtos = $session->get('page_import_preview', []);

        if (!$pageDtos) {
            $this->addFlash('error', 'No pages to import.');
            return $this->redirectToRoute('incc_post_import');
        }

        /** @var PageExportDto[] $pageDtos */
        $pageDtos = $session->get('page_import_preview', []);

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $options = new ImportOptionsDto();
        $options->createMissingCategories = $request->request->getBoolean('createMissingCategories', false);
        $options->createMissingTags = $request->request->getBoolean('createMissingTags', false);
        $options->overridePostDates = $request->request->getBoolean('overridePostDates', false);

        $result = $pageImportService->import($pageDtos, $currentUser, $options);

        if ($result->pagesImported > 0) {
            $this->addFlash('success', sprintf(
                'Imported %d pages, created %d categories and %d tags.',
                $result->pagesImported,
                $result->categoriesCreated,
                $result->tagsCreated
            ));
        } else {
            $this->addFlash('error', 'No pages imported.');
        }

        if (!empty($result->warnings)) {
            foreach ($result->warnings as $warning) {
                $this->addFlash('warning', $warning);
            }
        }

        $session->remove('page_import_preview');

        return $this->redirectToRoute('incc_post_list');
    }
}
