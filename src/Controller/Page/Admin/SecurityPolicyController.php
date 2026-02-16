<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\SecurityPolicy;
use Inachis\Form\SecurityPolicyType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for security policy management
 */
class SecurityPolicyController extends AbstractInachisController
{
    #[Route('/incc/admin/security-policy', name: 'incc_admin_security_policy', priority: 100)]
    public function edit(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Fetch the three policies (assume always exactly 3)
        $policies = $em->getRepository(SecurityPolicy::class)->findBy([], ['createdAt' => 'ASC']);

        // First policy editable
        $firstPolicy = $policies[0];

        $form = $this->createForm(SecurityPolicyType::class, $firstPolicy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Security policy updated!');
            return $this->redirectToRoute('security_policy');
        }

        // Active policy selection
        if ($request->isMethod('POST') && $request->request->has('active_policy')) {
            $activeId = $request->request->get('active_policy');

            foreach ($policies as $policy) {
                $policy->setIsActive($policy->getId()->toString() === $activeId);
            }

            $em->flush();
            $this->addFlash('success', 'Active policy updated!');
            return $this->redirectToRoute('security_policy');
        }


        $this->data['page']['title'] = 'Security Policy';
        $this->data['page']['tab'] = 'policies';
        $this->data['policies'] = $policies;
        $this->data['form'] = $form->createView();
        return $this->render('inadmin/page/admin/security_policy.html.twig', $this->data);
    }
}
