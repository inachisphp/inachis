<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Post;

use App\Controller\AbstractInachisController;
use App\Entity\Page;
use App\Entity\Url;
use App\Parser\MarkdownFileParser;
use App\Service\Page\PageFileImportService;
use App\Util\UrlNormaliser;
use DateTimeInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function App\Controller\gettype;

#[IsGranted('ROLE_ADMIN')]
class ImportController extends AbstractInachisController
{
    #[Route("/incc/import", name: "incc_post_import", methods: [ "GET" ])]
    public function index(): Response
    {
        $this->data['page']['tab'] = 'import';
        // @todo change text if handheld device
        return $this->render('inadmin/page/post/import.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/incc/import", name: "incc_post_process", methods: [ "POST", "PUT" ])]
    public function process(
        Request $request,
        PageFileImportService $pageFileImportService,
    ): JsonResponse {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        $lastResponse = $this->json('success', 200);
        $files = $request->files->get('markdownFiles');
        if(!empty($files) && !is_array($files)) {
            $files = [ $files ];
        }
        if(!empty($files)) {
            foreach ($files as $file) {
                if ($file->getError() != UPLOAD_ERR_OK) {
                    return $this->json('error', 400);
                }
                $lastResponseCode = $pageFileImportService->processFile($file);
                if ($lastResponseCode > 299) {
                    $this->json('error', $lastResponseCode);
                    break;
                }
            }
        } else {
            $lastResponse = $this->json('error', 400);
        }
        return $lastResponse;
    }
}
