<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\{User,UserPreference};
use Inachis\Form\UserType;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\User\UserPasskeyRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\PasskeyService;
use Inachis\Security\Authentication\TotpService;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Transformer\ImageTransformer;
use Inachis\Service\User\ProfileColorPalette;
use Random\RandomException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileController extends AbstractInachisController
{
    /**
     * List administrators
     *
     * @param Request $request
     * @param ContentQueryParameters $contentQueryParameters
     * @param UserBulkActionService $userBulkActionService
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route(
        "/incc/admin/list/{limit}/{offset}",
        name: 'incc_admin_list',
        requirements: [
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "limit" => 25, "offset" => 0, ],
        methods: [ "GET", "POST" ]
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        UserBulkActionService $userBulkActionService,
        UserRepository $userRepository,
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
                $count = $userBulkActionService->apply($action, $items);
                $this->addFlash('success', "Action '$action' applied to $count users.");
            }

            return $this->redirectToRoute('incc_admin_list');
        }

        $params = $viewStateManager->build(
            $request,
            'admin',
            new ViewStateDefaults(
                sort: 'displayName asc',
                view: 'table',
            ),
            $categoryRepository,
        );

        $this->viewModel->page->title = 'Users';
        $this->viewModel->page->tab = 'users';
        return $this->render('inadmin/page/admin/list.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $userRepository->getFiltered(
                $params->getFilters(),
                $params->getLimit(),
                $params->getOffset(),
                $params->getSort(),
            ),
            'form' => $form->createView(),
            'query' => $params,
        ]);
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
        $id = $request->attributes->getString('id');
        $isNew = ($id === 'new');

        $user = $isNew ? new User():
            $userRepository->findOneBy(
                [ 'username' => $request->attributes->getString('id') ]
            ) ?? new User();
        $preferences = $user->getPreferences();
        if ($preferences === null) {
            $preferences = new UserPreference($user);
            $user->setPreferences($preferences);
            $this->entityManager->persist($preferences);
        }
        /** @var Form $form */
        $form = $this->createForm(UserType::class, $user, [
            'validation_groups' => [ '' ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $enableDisable = $form->has('enableDisable') ? $form->get('enableDisable') : null;
            $delete = $form->has('delete') ? $form->get('delete') : null;

            if ($enableDisable instanceof \Symfony\Component\Form\ClickableInterface && $enableDisable->isClicked()) {
                $user->setActive(!$user->isEnabled());
            }
            if ($delete instanceof \Symfony\Component\Form\ClickableInterface && $delete->isClicked()) {
                $user->setRemoved(true);
            }

            if ($isNew) {
                $preferences->setColor(ProfileColorPalette::generate());
                $userAccountEmailService->registerNewUser(
                    $user,
                    [ 'viewModel' => $this->viewModel, ],
                    fn (string $token) => $this->generateUrl(
                        'incc_account_new-password',
                        [ 'token' => $token ]
                    )
                );
                $this->entityManager->persist($user);
            }
            $preferences->setTimezone(
                $request->request->all('user')['timezone'] ?? $preferences->getTimezone()
            );
            $preferences->setLocale(
                $request->request->all('user')['locale'] ?? $preferences->getLocale()
            );
            $preferences->setColor(
                $request->request->all('user')['color'] ?? $preferences->getColor()
            );

            $this->entityManager->flush();

            $this->addFlash('success', 'User details saved.');
            return $this->redirect($this->generateUrl('incc_admin_edit', [
                'id' => $user->getUsername(),
            ]));
        }

        $this->viewModel->page->title = 'Profile';
        $this->viewModel->page->tab = 'users';
        return $this->render('inadmin/page/admin/profile.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'heicSupported' => $imageTransformer->isHEICSupported(),
            'user' => $user,
        ]);
    }

    // -------------------------------------------------------------------------
    // TOTP management
    // -------------------------------------------------------------------------

    /**
     * Returns the TOTP setup data (secret + QR code URI) for the current user.
     *
     * @param TotpService $totpService
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/totp/setup", name: "incc_admin_totp_setup", methods: ["GET"])]
    public function totpSetup(TotpService $totpService): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->isTotpEnabled()) {
            return new JsonResponse(['error' => 'TOTP is already enabled.'], 400);
        }

        $secret = $totpService->generateSecret();
        // Store provisionally in the session; only persisted once confirmed
        $this->container->get('request_stack')->getCurrentRequest()?->getSession()
            ->set('inachis.totp.pending_secret', $secret);

        return new JsonResponse([
            'secret' => $secret,
            'qrUri'  => $totpService->getQrCodeUri($user->getUserIdentifier(), $secret),
        ]);
    }

    /**
     * Confirms and enables TOTP for the current user by verifying a code
     * against the pending secret stored in the session.
     *
     * @param Request     $request
     * @param TotpService $totpService
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/totp/enable", name: "incc_admin_totp_enable", methods: ["POST"])]
    public function totpEnable(Request $request, TotpService $totpService): JsonResponse
    {
        /** @var User $user */
        $user    = $this->security->getUser();
        $session = $request->getSession();
        $secret  = $session->get('inachis.totp.pending_secret');

        if (empty($secret)) {
            return new JsonResponse(['error' => 'No pending TOTP setup found. Request setup first.'], 400);
        }

        $body = json_decode($request->getContent(), true);
        $code = trim($body['code'] ?? '');

        if (!$totpService->verifyCode($secret, $code)) {
            return new JsonResponse(['error' => 'Invalid code. Please try again.'], 400);
        }

        $user->setTotpSecret($secret);
        $user->setTotpEnabled(true);
        $this->entityManager->flush();

        $session->remove('inachis.totp.pending_secret');

        return new JsonResponse(['success' => true]);
    }

    /**
     * Disables TOTP for the current user.
     *
     * @param Request     $request
     * @param TotpService $totpService
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/totp/disable", name: "incc_admin_totp_disable", methods: ["POST"])]
    public function totpDisable(Request $request, TotpService $totpService): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $body = json_decode($request->getContent(), true);
        $code = trim($body['code'] ?? '');

        if (!$user->isTotpEnabled() || empty($user->getTotpSecret())) {
            return new JsonResponse(['error' => 'TOTP is not enabled.'], 400);
        }

        if (!$totpService->verifyCode((string) $user->getTotpSecret(), $code)) {
            return new JsonResponse(['error' => 'Invalid code.'], 400);
        }

        $user->setTotpSecret(null);
        $user->setTotpEnabled(false);
        $this->entityManager->flush();

        $request->getSession()->remove('inachis.2fa.totp_verified');

        return new JsonResponse(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // Passkey management
    // -------------------------------------------------------------------------

    /**
     * Returns a WebAuthn creation challenge so the browser can register a new passkey.
     *
     * @param Request        $request
     * @param PasskeyService $passkeyService
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/passkey/register/challenge", name: "incc_admin_passkey_register_challenge", methods: ["GET"])]
    public function passkeyRegisterChallenge(Request $request, PasskeyService $passkeyService): JsonResponse
    {
        /** @var User $user */
        $user      = $this->security->getUser();
        $challenge = $passkeyService->generateChallenge();
        $request->getSession()->set('inachis.passkey.register_challenge', $challenge);

        $options = $passkeyService->buildCreationOptions($user, $challenge, $request->getHost());
        return new JsonResponse($options);
    }

    /**
     * Verifies the browser's registration response and saves the new passkey.
     *
     * @param Request        $request
     * @param PasskeyService $passkeyService
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/passkey/register/verify", name: "incc_admin_passkey_register_verify", methods: ["POST"])]
    public function passkeyRegisterVerify(Request $request, PasskeyService $passkeyService): JsonResponse
    {
        /** @var User $user */
        $user      = $this->security->getUser();
        $session   = $request->getSession();
        $challenge = $session->get('inachis.passkey.register_challenge');

        if (empty($challenge)) {
            return new JsonResponse(['error' => 'No active registration challenge.'], 400);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'Invalid request body.'], 400);
        }

        try {
            $passkey = $passkeyService->verifyAndSaveRegistration(
                $user,
                $challenge,
                $request->getHost(),
                $body,
                $body['name'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $session->remove('inachis.passkey.register_challenge');

        return new JsonResponse([
            'success' => true,
            'id'      => (string) $passkey->getId(),
            'name'    => $passkey->getName(),
        ]);
    }

    /**
     * Lists all passkeys registered for the current user.
     *
     * @param UserPasskeyRepository $passkeyRepository
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/passkey/list", name: "incc_admin_passkey_list", methods: ["GET"])]
    public function passkeyList(UserPasskeyRepository $passkeyRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $passkeys = array_map(
            static fn($pk) => [
                'id'        => (string) $pk->getId(),
                'name'      => $pk->getName(),
                'createdAt' => $pk->getCreatedAt()->format('Y-m-d H:i'),
            ],
            $passkeyRepository->findByUser($user)
        );

        return new JsonResponse($passkeys);
    }

    /**
     * Deletes a specific passkey belonging to the current user.
     *
     * @param string                $id
     * @param UserPasskeyRepository $passkeyRepository
     * @return JsonResponse
     */
    #[Route("/incc/admin/security/passkey/{id}", name: "incc_admin_passkey_delete", methods: ["DELETE"])]
    public function passkeyDelete(string $id, UserPasskeyRepository $passkeyRepository): JsonResponse
    {
        /** @var User $user */
        $user    = $this->security->getUser();
        $passkey = $passkeyRepository->find($id);

        if ($passkey === null || (string) $passkey->getUser()->getId() !== (string) $user->getId()) {
            return new JsonResponse(['error' => 'Passkey not found.'], 404);
        }

        $this->entityManager->remove($passkey);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
