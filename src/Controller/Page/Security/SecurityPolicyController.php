<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Form\SecurityPolicyType;
use Inachis\Repository\Security\SecurityPolicyRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for security policy management.
 */
class SecurityPolicyController extends AbstractInachisController
{
    #[Route(
        '/incp/admin/security-policy',
        name: 'incp_admin_security_policy',
        priority: 100,
    )]
    #[RequiresPermission(
        resource: PermissionResource::PASSWORD_POLICY,
        action: PermissionAction::MANAGE,
    )]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SecurityPolicyRepository $securityPolicyRepository,
    ): Response {
        $policies = $securityPolicyRepository->findAll();

        if (3 !== count($policies)) {
            throw new \RuntimeException(sprintf('Expected exactly 3 security policies, found %d', count($policies)));
        }

        $selectedIdentifier = $request->query->getString(
            'policy',
            'custom',
        );

        $selectedPolicy = null;

        foreach ($policies as $policy) {
            if ($policy->getIdentifier() === $selectedIdentifier) {
                $selectedPolicy = $policy;
                break;
            }
        }

        if (!$selectedPolicy instanceof SecurityPolicy) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(
            SecurityPolicyType::class,
            $selectedPolicy,
        );

        if (!$selectedPolicy->isReadOnly()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Security policy updated.',
                );

                return $this->redirectToRoute(
                    'incp_admin_security_policy',
                    [
                        'policy' => $selectedPolicy->getIdentifier(),
                    ],
                );
            }
        }

        if (
            $request->isMethod('POST')
            && $request->request->has('active_policy')
        ) {
            $activeId = $request->request->getString('active_policy');

            foreach ($policies as $policy) {
                $policy->setActive(
                    $policy->getId()?->toString() === $activeId,
                );
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Active security policy updated.',
            );

            return $this->redirectToRoute(
                'incp_admin_security_policy',
            );
        }

        $this->viewModel->page->title = 'Security Policies';
        $this->viewModel->page->tab = $selectedPolicy->getIdentifier();

        return $this->render(
            'inadmin/page/admin/security_policy.html.twig',
            [
                'viewModel' => $this->viewModel,
                'form' => $form?->createView(),
                'policy' => $selectedPolicy,
                'policies' => $policies,
            ],
        );
    }
}
