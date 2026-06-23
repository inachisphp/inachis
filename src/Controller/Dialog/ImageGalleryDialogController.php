<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Media\Image;
use Inachis\Form\ImageType;
use Inachis\Repository\Media\ImageRepository;
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
        return $this->render('inadmin/dialog/image-manager.html.twig', [
            'form' => $this->createForm(ImageType::class)->createView(),
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'dataset' => [],
        ]);
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
        /** @var array{keyword?: string} */
        $filters = array_filter($request->request->all('filter'));
        $offset = $request->attributes->getInt('offset', 0);
        $limit = $request->attributes->getInt('limit', $imageRepository::MAX_ITEMS_TO_SHOW_ADMIN);

        return $this->render('inadmin/partials/gallery.html.twig', [
            'images' => $imageRepository->getFiltered(
                $filters,
                $offset,
                $limit
            ),
            'query' => [
                'offset' => $offset,
                'limit' => $limit
            ],
        ]);
    }
}
