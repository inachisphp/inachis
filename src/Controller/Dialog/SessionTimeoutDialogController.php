<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use App\Controller\ZipStream;
use App\Entity\Page;
use App\Parser\ArrayToMarkdown;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted('ROLE_ADMIN')]
class SessionTimeoutDialogController extends AbstractInachisController
{
    #[Route('/incc/keep-alive', methods: [ 'POST' ])]
    public function keepAlive(): JsonResponse
    {
        return new JsonResponse(['time' => date(
            'Y-m-d\TH:i:s\Z',
            strtotime('+' . ini_get('session.gc_maxlifetime') . ' seconds')
        )]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/incc/ax/sessionTimeout/get', methods: [ 'POST' ])]
    public function showDialog(Request $request): Response
    {
        return $this->render('inadmin/dialog/session_timeout.html.twig', $this->data);
    }
}
