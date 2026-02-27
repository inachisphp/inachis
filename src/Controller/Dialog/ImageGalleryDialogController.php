<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Image;
use Inachis\Form\ImageType;
use Inachis\Repository\ImageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ImageGalleryDialogController extends AbstractInachisController
{
    /**
     * @return Response
     */
    #[Route('/incc/ax/imageManager/get', methods: [ 'POST' ])]
    public function getImageManagerList(): Response
    {
        $this->data['form'] = $this->createForm(ImageType::class)->createView();
        $this->data['allowedTypes'] = Image::ALLOWED_MIME_TYPES;
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
    public function getImageList(
        Request $request,
        ImageRepository $imageRepository,
    ): Response {
        $filters = array_filter($request->request->all('filter', []));
        $offset = (int) $request->attributes->get('offset', 0);
        $limit = (int) $request->attributes->get(
            'limit',
            $imageRepository::MAX_ITEMS_TO_SHOW_ADMIN
        );
        $this->data['images'] = $imageRepository->getFiltered(
            $filters,
            $offset,
            $limit
        );
        $this->data['query']['offset'] = $offset;
        $this->data['query']['limit'] = $limit;
        return $this->render('inadmin/partials/gallery.html.twig', $this->data);
    }
}
