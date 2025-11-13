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
use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Tag;
use App\Form\ImageType;
use App\Parser\ArrayToMarkdown;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ImageGalleryDialogController extends AbstractInachisController
{
    /**
     * @return Response
     */
    #[Route('/incc/ax/imageManager/get', methods: [ 'POST' ])]
    public function getImageManagerList(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->data['form'] = $this->createForm(ImageType::class)->createView();
        $this->data['allowedTypes'] = Image::ALLOWED_TYPES;
        $this->data['dataset'] = [];
        return $this->render('inadmin/dialog/image-manager.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/incc/ax/imageManager/getImages/{offset}/{limit}',
        requirements: [
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 25 ],
        methods: [ "POST" ],
    )]
    public function getImageList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $filters = array_filter($request->request->all('filter', []));
        $offset = (int) $request->attributes->get('offset', 0);
        $limit = (int) $request->attributes->get(
            'limit',
            $this->entityManager->getRepository(Image::class)::MAX_ITEMS_TO_SHOW_ADMIN
        );
        $this->data['images'] = $this->entityManager->getRepository(Image::class)->getFiltered(
            $filters,
            $offset,
            $limit
        );
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        return $this->render('inadmin/partials/gallery.html.twig', $this->data);
    }
}
