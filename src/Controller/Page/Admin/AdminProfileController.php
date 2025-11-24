<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Admin;

use App\Controller\AbstractInachisController;
use App\Entity\User;
use App\Form\UserType;
use App\Service\PasswordResetTokenService;
use App\Transformer\ImageTransformer;
use App\Util\Base64EncodeFile;
use App\Util\RandomColorPicker;
use DateTime;
use Exception;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_ADMIN')]
class AdminProfileController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
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
    public function list(Request $request): Response
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            foreach ($request->request->all('items') as $item) {
                $selectedItem = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $item]);
                if ($selectedItem !== null) {
                    if ($request->request->get('delete') !== null) {
                        $selectedItem->setRemoved(true);
                    } elseif ($request->request->get('enable') !== null) {
                        $selectedItem->setActive(true);
                    } elseif ($request->request->get('disable') !== null) {
                        $selectedItem->setActive(false);
                    }
                    $selectedItem->setModDate(new DateTime('now'));
                    $this->entityManager->persist($selectedItem);
                }
            }
            $this->entityManager->flush();
            return $this->redirectToRoute('incc_admin_list');
        }

        $filters = array_filter($request->request->all('filter', []));
        if ($request->isMethod('post')) {
            $_SESSION['admin_filters'] = $filters;
        } elseif (isset($_SESSION['admin_filters'])) {
            $filters = $_SESSION['admin_filters'];
        }
        $offset = (int) $request->request->get('offset', 0);
        $limit = $this->entityManager->getRepository(User::class)->getMaxItemsToShow();
        $this->data['form'] = $form->createView();
        $this->data['dataset'] = $this->entityManager->getRepository(User::class)->getFiltered(
            $filters,
            $offset,
            $limit
        );
        $this->data['filters'] = $filters;
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['title'] = 'Users';
        return $this->render('inadmin/page/admin/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param ImageTransformer $imageTransformer
     * @param MailerInterface $mailer
     * @param PasswordResetTokenService $tokenService
     * @param ValidatorInterface $validator
     * @return Response
     * @throws RandomException
     */
    #[Route("/incc/admin/{id}", name: "incc_admin_edit", methods: [ "GET", "POST" ])]
    public function edit(
        Request $request,
        ImageTransformer $imageTransformer,
        MailerInterface $mailer,
        PasswordResetTokenService $tokenService,
        ValidatorInterface $validator,
    ): Response {
        $user = $request->attributes->get('id') !== 'new' ?
            $this->entityManager->getRepository(User::class)->findOneBy(
                [ 'username' => $request->attributes->get('id') ]
            ):
            new User();
        $form = $this->createForm(UserType::class, $user, [
            'validation_groups' => [ '' ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('enableDisable') && $form->get('enableDisable')->isClicked()) {
                $user->setActive(!$user->isEnabled());
            }
            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $user->setRemoved(true);
            }
            $user->setModDate(new DateTime('now'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($request->attributes->get('id') === 'new') {
                $data = $tokenService->createResetRequestForEmail($user->getEmail());
                $user->setColor(RandomColorPicker::generate());
                try {
                    $email = (new TemplatedEmail())
                        ->to(new Address($user->getEmail()))
                        ->subject('Welcome to ' . $this->data['settings']['siteTitle'])
                        ->htmlTemplate('inadmin/emails/registration.html.twig')
                        ->textTemplate('inadmin/emails/registration.txt.twig')
                        ->context([
                            'name' => $user->getDisplayName(),
                            'url' => $this->generateUrl('incc_account_new-password', [ 'token' => $data['token']]),
                            'expiresAt' => $data['expiresAt']->format('l jS F Y \a\\t H:i'),
                            'settings' => $this->data['settings'],
                            'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
                        ])
                    ;
                    $mailer->send($email);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('warning', 'Error while sending mail: ' . $e->getMessage());
                }
            }

            $this->addFlash('success', 'User details saved.');
            return $this->redirect($this->generateUrl('incc_admin_edit', [
                'id' => $user->getUsername(),
            ]));
        }

        $this->data['user'] = $user;
        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = 'Profile';
        $this->data['heicSupported'] = $imageTransformer->isHEICSupported();

        return $this->render('inadmin/page/admin/profile.html.twig', $this->data);
    }
}
