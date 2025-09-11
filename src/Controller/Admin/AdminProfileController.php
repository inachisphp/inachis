<?php

namespace App\Controller\Admin;

use App\Controller\AbstractInachisController;
use App\Entity\User;
use App\Form\UserType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/admin-management", methods: [ 'GET', 'POST' ])]
    public function adminList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(User::class)->getMaxItemsToShow();
        $this->data['dataset'] = $this->entityManager->getRepository(User::class)->getAll(
            $offset,
            $limit
        );
        $this->data['form'] = $form->createView();
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['title'] = 'Users';

        return $this->render('inadmin/admin/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/admin/{id}", methods: [ "GET", "POST" ])]
    public function adminDetails(Request $request, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $request->get('id')]);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user->setModDate(new \DateTime('now'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'User details saved.');
            return $this->redirect($this->generateUrl('app_admin_adminprofile_admindetails', [
                'id' => $user->getUsername(),
            ]));
        }

        $this->data['user'] = $user;
        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = 'Profile';

        return $this->render('inadmin/admin/profile.html.twig', $this->data);
    }
}
