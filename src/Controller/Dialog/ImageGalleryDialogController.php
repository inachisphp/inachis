<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Media\Image;
use Inachis\Form\ImageType;
use Inachis\Repository\Media\ImageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageGalleryDialogController extends AbstractInachisController
{
    #[Route('/incp/ax/imageManager/get', methods: ['POST'])]
    public function getImageManagerList(): Response
    {
        return $this->render('inadmin/dialog/image-manager.html.twig', [
            'form' => $this->createForm(ImageType::class)->createView(),
            'allowedTypes' => Image::ALLOWED_MIME_TYPES,
            'dataset' => [],
        ]);
    }

    #[Route('/incp/ax/imageManager/getImages/{limit}/{offset}',
        requirements: [
            'limit' => "\d+",
            'offset' => "\d+",
        ],
        defaults: ['limit' => 25, 'offset' => 0],
        methods: ['POST'],
    )]
    public function getImageList(
        Request $request,
        ImageRepository $imageRepository,
    ): Response {
        /** @var array{keyword?: string} */
        $filters = array_filter($request->request->all('filter'));
        $limit = $request->attributes->getInt('limit', $imageRepository::MAX_ITEMS_TO_SHOW_ADMIN);
        $offset = $request->attributes->getInt('offset', 0);

        return $this->render('inadmin/partials/gallery.html.twig', [
            'images' => $imageRepository->getFiltered(
                $filters,
                $limit,
                $offset,
            ),
            'query' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}
