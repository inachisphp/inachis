<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\NavigationTab;
use Inachis\Form\NavigationTabType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\NavigationTabRepository;
use Inachis\Service\Navigation\NavigationTabService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Annotation\IsGranted;

/**
 * Controller for navigation tabs
 */
#[IsGranted('ROLE_ADMIN')]
class NavigationTabController extends AbstractInachisController
{
    /**
     * List all navigation tabs
     * 
     * @param Request $request
     * @param NavigationTabRepository $navigationTabRepository
     * @param NavigationTabService $navigationTabService
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/incc/settings/navigation', name: 'incc_settings_navigation_list')]
    public function index(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        NavigationTabRepository $navigationTabRepository,
        NavigationTabService $navigationTabService,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('enable') ? 'enable' :
                ($request->request->has('disable') ? 'disable' : null));

            if ($action !== null && !empty($items)) {
                $count = $navigationTabService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count tabs");
            }
            return $this->redirectToRoute('incc_settings_navigation_list');
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $navigationTabRepository,
            'navigationTab',
            'position asc',
        );
        $this->data['query'] = $contentQuery;
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $navigationTabRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
            $contentQuery['sort'],
        );
        $this->data['page']['title'] = 'Navigation Tabs';
        $this->data['page']['tab'] = 'settings';
        return $this->render('inadmin/page/settings/navigation-list.html.twig', $this->data);
    }

    /**
     * Saves changes to a given navigation tab
     * 
     * @param Request $request
     * @param NavigationTab $tab
     * @param EntityManagerInterface $em
     * @return Response
     */
    // #[Route('/incc/settings/navigation/{id}/edit', name: 'incc_settings_navigation_edit', methods: ['POST'])]
    // public function edit(
    //     Request $request,
    //     NavigationTab $tab,
    //     EntityManagerInterface $em
    // ): Response {
    //     $form = $this->createForm(NavigationTabType::class, $tab);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $em->flush();
    //     }
    //     // $navigationService->clearCache();

    //     return $this->redirectToRoute('incc_settings_navigation_list');
    // }

    /**
     * Add/Edit a navigation tab
     * 
     * @param Request $request
     * @param NavigationTabRepository $navigationTabRepository
     * @param NavigationTabService $navigationTabService
     * @return Response
     */
    #[Route('/incc/settings/navigation/edit/{id}', name: 'incc_settings_navigation_edit')]
    public function edit(
        Request $request, 
        NavigationTabRepository $navigationTabRepository,
        NavigationTabService $navigationTabService,
    ): Response {
        $id = $request->attributes->get('id');
        $isNew = ($id === 'new');

        $tab = $isNew ? new NavigationTab(): 
            $navigationTabRepository->findOneBy(
                [ 'id' => $request->attributes->get('id') ]
            );
        /** @var Form $form */
        $form = $this->createForm(NavigationTabType::class, $tab);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $navigationTabService->add($tab);
            return $this->redirectToRoute('incc_settings_navigation_list');
        }

        $this->data['page']['title'] = 'Navigation Tab';
        $this->data['page']['tab'] = 'settings';
        $this->data['form'] = $form->createView();

        return $this->render('inadmin/page/settings/navigation-edit.html.twig', $this->data);
    }

    /**
     * Move a navigation tab up
     *
     * @param NavigationTab $tab
     * @param NavigationTabManager $manager
     * @return Response
     */
    #[Route('/incc/settings/navigation/{id}/up', name: 'incc_settings_navigation_up', methods: ['POST'])]
    public function moveUp(
        NavigationTab $tab,
        NavigationTabService $manager
    ): Response {
        $manager->moveUp($tab);

        return $this->redirectToRoute('incc_settings_navigation_list');
    }

    /**
     * Move a navigation tab down
     *
     * @param NavigationTab $tab
     * @param NavigationTabManager $manager
     * @return Response
     */
    #[Route('/incc/settings/navigation/{id}/down', name: 'incc_settings_navigation_down', methods: ['POST'])]
    public function moveDown(
        NavigationTab $tab,
        NavigationTabService $manager
    ): Response {
        $manager->moveDown($tab);

        return $this->redirectToRoute('incc_settings_navigation_list');
    }

    /**
     * Reorder all tabs based on provided JSON list
     *
     * @param Request $request
     * @param NavigationTabService $manager
     * @return JsonResponse
     */
    #[Route('/incc/settings/navigation/reorder', name: 'incc_settings_navigation_reorder', methods: ['POST'])]
    public function reorder(
        Request $request,
        NavigationTabService $manager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid payload'], 400);
        }

        $updated = $manager->reorderTabs($data);

        return $this->json(['success' => $updated]);
    }
}