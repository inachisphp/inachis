<?php

namespace App\Controller\Admin;

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
use ReflectionProperty;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    #[Route("/incc/admin-management", methods: [ 'GET', 'POST' ])]
    public function adminList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $filters = array_filter($request->get('filter', []));
        if ($request->isMethod('post')) {
            $_SESSION['series_filters'] = $filters;
        } elseif (isset($_SESSION['series_filters'])) {
            $filters = $_SESSION['series_filters'];
        }

        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(User::class)->getMaxItemsToShow();
        $this->data['dataset'] = $this->entityManager->getRepository(User::class)->getFiltered(
            $filters,
            $offset,
            $limit
        );
        $this->data['form'] = $form->createView();
        $this->data['filters'] = $filters;
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['title'] = 'Users';

        return $this->render('inadmin/admin/list.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param ImageTransformer $imageTransformer
     * @param MailerInterface $mailer
     * @param PasswordResetTokenService $tokenService
     * @return Response
     * @throws RandomException
     */
    #[Route("/incc/admin/{id}", methods: [ "GET", "POST" ])]
    #[Route("/incc/admin/new", name: "app_admin_new", methods: [ "GET", "POST" ])]
    public function adminDetails(
        Request $request,
        ImageTransformer $imageTransformer,
        MailerInterface $mailer,
        PasswordResetTokenService $tokenService,
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $request->get('id') !== 'new' ?
            $this->entityManager->getRepository(User::class)->findOneBy(['username' => $request->get('id')]):
            new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $user->setModDate(new DateTime('now'));
            $reflection = new ReflectionProperty(User::class, 'id');

            if (!$reflection->isInitialized($user)) {
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
                            'url' => $this->generateUrl('app_account_newpassword', [ 'token' => $data['token']]),
                            'expiresAt' => $data['expiresAt']->format('l jS F Y \a\\t H:i'),
                            'settings' => $this->data['settings'],
                            'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
                        ])
                    ;
                    $mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('warning', 'Error while sending mail: ' . $e->getMessage());
                }
            }
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
        $this->data['heicSupported'] = $imageTransformer->isHEICSupported();

        return $this->render('inadmin/admin/profile.html.twig', $this->data);
    }
}
