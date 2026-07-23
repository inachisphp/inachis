<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\System\NavigationTab;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\NavigationTabType;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\System\NavigationTabRepository;
use Inachis\Service\Navigation\NavigationTabService;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for navigation tabs
 */
class NavigationTabController extends AbstractInachisController
{
    /**
     * List all navigation tabs
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @param NavigationTabRepository $navigationTabRepository
     * @param NavigationTabService $navigationTabService
     * @param ViewStateManager $viewStateManager
     * @return Response
     */
    #[Route('/incp/settings/navigation', name: 'incp_settings_navigation_list')]
    #[RequiresPermission(
        resource: PermissionResource::NAVIGATION,
        action: PermissionAction::VIEW
    )]
    public function index(
        Request $request,
        CategoryRepository $categoryRepository,
        NavigationTabRepository $navigationTabRepository,
        NavigationTabService $navigationTabService,
        ViewStateManager $viewStateManager,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            /** @var list<string> */
            $items = $request->request->all('items');
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('enable') ? 'enable' :
                ($request->request->has('disable') ? 'disable' : null));

            if ($action !== null) {
                $count = $navigationTabService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count tabs");
            }
            return $this->redirectToRoute('incp_settings_navigation_list');
        }

        $params = $viewStateManager->build(
            $request,
            'navigationTab',
            new ViewStateDefaults(
                sort: 'position asc',
                view: 'table',
            ),
            $categoryRepository,
        );

        $this->viewModel->page->title = 'Navigation Tabs';
        $this->viewModel->page->tab = 'settings';
        return $this->render('inadmin/page/settings/navigation-list.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $navigationTabRepository->getFiltered(
                $params->getLimit(),
                $params->getOffset(),
            ),
            'form' => $form->createView(),
            'query' => $params,
        ]);
    }

    /**
     * Add/Edit a navigation tab
     *
     * @param Request $request
     * @param NavigationTabRepository $navigationTabRepository
     * @param NavigationTabService $navigationTabService
     * @return Response
     */
    #[Route('/incp/settings/navigation/edit/{id}', name: 'incp_settings_navigation_edit')]
    #[RequiresPermission(
        resource: PermissionResource::NAVIGATION,
        action: PermissionAction::VIEW
    )]
    public function edit(
        Request $request,
        NavigationTabRepository $navigationTabRepository,
        NavigationTabService $navigationTabService,
    ): Response {
        $id = $request->attributes->getString('id');
        $isNew = ($id === 'new');

        $tab = $isNew ? new NavigationTab():
        $navigationTabRepository->findOneBy(
            [ 'id' => $request->attributes->getString('id') ]
        );
        $form = $this->createForm(NavigationTabType::class, $tab);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $tab instanceof NavigationTab) {
            $navigationTabService->add($tab);
            return $this->redirectToRoute('incp_settings_navigation_list');
        }

        $this->viewModel->page->title = 'Navigation Tab';
        $this->viewModel->page->tab = 'settings';
        return $this->render('inadmin/page/settings/navigation-edit.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView()
        ]);
    }

    /**
     * Move a navigation tab up
     *
     * @param NavigationTab $tab
     * @param NavigationTabService $manager
     * @return Response
     */
    #[Route('/incp/settings/navigation/{id}/up', name: 'incp_settings_navigation_up', methods: ['POST'])]
    #[RequiresPermission(
        resource: PermissionResource::NAVIGATION,
        action: PermissionAction::EDIT
    )]
    public function moveUp(
        NavigationTab $tab,
        NavigationTabService $manager
    ): Response {
        $manager->moveUp($tab);

        return $this->redirectToRoute('incp_settings_navigation_list');
    }

    /**
     * Move a navigation tab down
     *
     * @param NavigationTab $tab
     * @param NavigationTabService $manager
     * @return Response
     */
    #[Route('/incp/settings/navigation/{id}/down', name: 'incp_settings_navigation_down', methods: ['POST'])]
    #[RequiresPermission(
        resource: PermissionResource::NAVIGATION,
        action: PermissionAction::EDIT
    )]
    public function moveDown(
        NavigationTab $tab,
        NavigationTabService $manager
    ): Response {
        $manager->moveDown($tab);

        return $this->redirectToRoute('incp_settings_navigation_list');
    }

    /**
     * Reorder all tabs based on provided JSON list
     *
     * @param Request $request
     * @param NavigationTabService $manager
     * @return JsonResponse
     */
    #[Route('/incp/settings/navigation/reorder', name: 'incp_settings_navigation_reorder', methods: ['POST'])]
    #[RequiresPermission(
        resource: PermissionResource::NAVIGATION,
        action: PermissionAction::EDIT
    )]
    public function reorder(
        Request $request,
        NavigationTabService $manager,
    ): JsonResponse {
        /** @var array{id?: string, order?: list<string>}|array{}|null */
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid payload'], 400);
        }

        $updated = $manager->reorderTabs($data);

        return $this->json(['success' => $updated]);
    }
}
