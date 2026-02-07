<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\{Image,Page,Series,Tag,Url};
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ToolsIndexController extends AbstractInachisController
{
    #[Route("/incc/tools", name: 'incc_tools_index')]
    public function index(): Response
    {
        $this->data['page']['title'] = 'Tools';
        $this->data['page']['tab'] = 'tools';
        return $this->render('inadmin/page/tools/list.html.twig', $this->data);
    }

    // /**
    //  * @param LoggerInterface $logger
    //  * @param Request $request
    //  * @return RedirectResponse
    //  * @throws \Doctrine\DBAL\ConnectionException
    //  */
    // #[Route("/incc/settings/wipe", methods: [ "POST" ])]
    // public function wipe(LoggerInterface $logger, Request $request): RedirectResponse
    // {
    //     if ($request->request->get('confirm', false)) {
    //         $logger->info('Wiping all content');
    //         $this->entityManager->getRepository(Image::class)->wipe($logger);
    //         $this->entityManager->getRepository(Page::class)->wipe($logger);
    //         $this->entityManager->getRepository(Series::class)->wipe($logger);
    //         $this->entityManager->getRepository(Tag::class)->wipe($logger);
    //         $this->entityManager->getRepository(Url::class)->wipe($logger);
    //     }
    //     return $this->redirectToRoute('inachis_settings_index');
    // }
}
