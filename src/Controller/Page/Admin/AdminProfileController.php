<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User;
use Inachis\Form\UserType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\UserRepository;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Transformer\ImageTransformer;
use Random\RandomException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param UserBulkActionService $userBulkActionService
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route(
        "/incc/admin/list/{offset}/{limit}",
        name: 'incc_admin_list',
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 25 ],
        methods: [ "GET", "POST" ]
    )]
    public function list(
        Request $request,
        ContentQueryParameters $contentQueryParameters,
        UserBulkActionService $userBulkActionService,
        UserRepository $userRepository,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            $items = $request->request->all('items') ?? [];
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('enable') ? 'enable' :
                ($request->request->has('disable') ? 'disable' : null));

            if ($action !== null && !empty($items)) {
                $count = $userBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count users.");
            }

            return $this->redirectToRoute('incc_admin_list');
        }

        $contentQuery = $contentQueryParameters->process(
            $request,
            $userRepository,
            'admin',
            'displayName asc',
        );
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $userRepository->getFiltered(
            $contentQuery['filters'],
            $contentQuery['offset'],
            $contentQuery['limit'],
        );
        $this->data['query'] = $contentQuery;
        $this->data['page']['title'] = 'Users';
        $this->data['page']['tab'] = 'users';
        return $this->render('inadmin/page/admin/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param ImageTransformer $imageTransformer
     * @param UserAccountEmailService $userAccountEmailService
     * @param UserRepository $userRepository
     * @return Response
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    #[Route("/incc/admin/{id}", name: "incc_admin_edit", methods: [ "GET", "POST" ], priority: -100)]
    public function edit(
        Request $request,
        ImageTransformer $imageTransformer,
        UserAccountEmailService $userAccountEmailService,
        UserRepository $userRepository,
    ): Response {
        $id = $request->attributes->get('id');
        $isNew = ($id === 'new');

        $user = $isNew ? new User(): 
            $userRepository->findOneBy(
                [ 'username' => $request->attributes->get('id') ]
            );
        /** @var Form $form */
        $form = $this->createForm(UserType::class, $user, [
            'validation_groups' => [ '' ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getClickedButton()->getName() === 'enableDisable') {
                $user->setActive(!$user->isEnabled());
            }
            if ($form->getClickedButton()->getName() === 'delete') {
                $user->setRemoved(true);
            }
            $user->setModDate(new DateTimeImmutable());

            if ($isNew) {
                $userAccountEmailService->registerNewUser(
                    $user,
                    $this->data,
                    fn (string $token) => $this->generateUrl(
                        'incc_account_new-password',
                        [ 'token' => $token ]
                    )
                );
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'User details saved.');
            return $this->redirect($this->generateUrl('incc_admin_edit', [
                'id' => $user->getUsername(),
            ]));
        }

        $this->data['user'] = $user;
        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = 'Profile';
        $this->data['page']['tab'] = 'users';
        $this->data['heicSupported'] = $imageTransformer->isHEICSupported();

        return $this->render('inadmin/page/admin/profile.html.twig', $this->data);
    }
}
