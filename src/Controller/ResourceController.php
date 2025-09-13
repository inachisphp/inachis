<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResourceController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/resources/{type}/{offset}/{limit}",
        requirements: [
            "type" => "(images|downloads)",
            "offset" => "\d+",
            "limit" => "\d+"
        ],
        defaults: [ "offset" => 0, "limit" => 10 ],
        methods: [ "GET", "POST" ],
    )]
    public function resourcesList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $typeClass = match ($request->request?->get('type')) {
            'downloads' => Download::class,
            default => Image::class,
        };
        $type = substr(strrchr($typeClass, '\\'), 1);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_resource_resourceslist', [
                'type' => $request->get('type'),
            ]))
            ->getForm()
        ;
        $form->handleRequest($request);
        $filters = array_filter($request->get('filter', []));
        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository($typeClass)->getMaxItemsToShow();
        $this->data['dataset'] = $this->entityManager->getRepository($typeClass)->getFiltered(
            $filters,
            $offset,
            $limit
        );
        $this->data['form'] = $form->createView();
        $this->data['page']['type'] = $request->get('type');
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['tab'] = $type;
        $this->data['page']['title'] = $type . 's';

        return $this->render('inadmin/_resources.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/incc/resource/image/upload", methods: [ "POST", "PUT" ])]
    public function uploadImage(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        dump($request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/incc/resource/image/save", methods: [ "POST" ])]
    public function saveImage(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $image = null;
//        if (!empty($request->files))
//        {
//
//        }

        if (!empty($request->get('image'))) {
            $image = new Image();
            $form = $this->createForm(ImageType::class, $image);
            $form->handleRequest($request);

            $imageInfo = getimagesize($image->getFilename());
            $image->setDimensionX($imageInfo[0]);
            $image->setDimensionY($imageInfo[1]);
            $image->setFiletype($imageInfo['mime']);
            $image->setChecksum(sha1_file($image->getFilename()));
            unset($imageInfo);

            $this->entityManager->persist($image);
            $this->entityManager->flush();
        }

//        foreach ($request->files as $file) {
//            if ($file->getError() != UPLOAD_ERR_OK) {
//                return $this->json('error', 400);
//            }
//            $post = $parser->parse($this->getDoctrine()->getManager(), file_get_contents($file->getRealPath()));

        return $this->json([
            'result' => 'success',
            'image' => [
                'id' => $image->getId(),
                'filename' => $image->getFilename(),
                'altText' => $image->getAltText(),
                'title' => $image->getTitle(),
            ]
        ], 200);
    }
}
