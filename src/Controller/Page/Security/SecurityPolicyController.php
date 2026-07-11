<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Form\SecurityPolicyType;
use Inachis\Repository\Security\SecurityPolicyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for security policy management
 */
class SecurityPolicyController extends AbstractInachisController
{
    #[Route('/incc/admin/security-policy', name: 'incc_admin_security_policy', priority: 100)]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SecurityPolicyRepository $securityPolicyRepository,
    ): Response {
        // Fetch the three policies (assume always exactly 3)
        $policies = $securityPolicyRepository->findBy([], ['createdAt' => 'ASC']);
        if (count($policies) !== 3) {
            throw new \RuntimeException('Expected exactly 3 security policies, found ' . count($policies));
        }
        
        // First policy editable
        $firstPolicy = $policies[0];

        $form = $this->createForm(SecurityPolicyType::class, $firstPolicy);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Security policy updated!');
            return $this->redirectToRoute('security_policy');
        }

        // Active policy selection
        if ($request->isMethod('POST') && $request->request->has('active_policy')) {
            $activeId = $request->request->getString('active_policy');

            foreach ($policies as $policy) {
                $policy->setIsActive($policy->getId()?->toString() === $activeId);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Active policy updated!');
            return $this->redirectToRoute('security_policy');
        }

        $this->viewModel->page->title = 'Security Policy';
        $this->viewModel->page->tab = 'policies';
        return $this->render('inadmin/page/admin/security_policy.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'policies' => $policies,
        ]);
    }
}
