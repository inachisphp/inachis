<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ResourceController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route("/incc/resources", methods: [ "GET" ])]
    public function resourcesList(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        $offset = (int) $request->get('offset', 0);
        $limit = $this->entityManager->getRepository(Image::class)->getMaxItemsToShow();
        $this->data['dataset'] = $this->entityManager->getRepository(Image::class)->getAll(
            $offset,
            $limit
        );
        $this->data['form'] = $form->createView();
        $this->data['page']['offset'] = $offset;
        $this->data['page']['limit'] = $limit;
        $this->data['page']['title'] = 'Users';

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

            $this->getDoctrine()->getManager()->persist($image);
            $this->getDoctrine()->getManager()->flush();
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
