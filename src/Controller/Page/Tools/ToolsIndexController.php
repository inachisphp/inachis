<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ToolsIndexController extends AbstractInachisController
{
    /**
     * Index page for tools
     *
     * @return Response
     */
    #[Route("/incc/tools", name: 'incc_tools_index')]
    public function index(): Response
    {
        
        $this->viewModel->page->title = 'Tools';
        $this->viewModel->page->tab = 'tools';
        return $this->render('inadmin/page/tools/list.html.twig', [
            'viewModel' => $this->viewModel,
            'environment' => $this->getParameter('kernel.environment'),
        ]);
    }

    /**
     * Storage usage page
     *
     * @param ImageRepository $imageRepository
     * @param PageRepository $pageRepository
     * @return Response
     */
    #[Route("/incc/tools/storage", name: 'incc_tools_storage')]
    public function storage(ImageRepository $imageRepository, PageRepository $pageRepository): Response
    {
        $this->viewModel->page->title = 'Storage';
        $this->viewModel->page->tab = 'tools';
        return $this->render('inadmin/page/tools/storage.html.twig', [
            'viewModel' => $this->viewModel,
            'environment' => $this->getParameter('kernel.environment'),
            'storage' => [
                'biggestImages' => $imageRepository->getAll(0, 10, [], [['q.filesize', 'DESC']]),
                'images' => $imageRepository->getDiskUsage(),
                'topPagesBySize' => $pageRepository->getTopPagesByImageSize(25),
            ],
        ]);
    }
}
