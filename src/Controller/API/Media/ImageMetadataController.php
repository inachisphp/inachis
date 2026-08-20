<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Media;

use Inachis\Exception\Ai\AiException;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Service\Ai\AiExceptionResponseFactory;
use Inachis\Service\Ai\AiVisionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Image metadata API.
 */
class ImageMetadataController extends AbstractController
{
	#[Route('/incp/api/resource/image/{id}/generate-metadata', name: 'incp_resource_image_ai_metadata', methods: ['POST'])]
	public function generateAiMetadata(
		string $id,
		AiExceptionResponseFactory $aiExceptionResponseFactory,
		AiVisionManager $aiVisionManager,
		ImageRepository $imageRepository,
	): JsonResponse {
		$image = $imageRepository->find($id);
		if (!$image) {
			return new JsonResponse([
				'error' => 'Image not found.',
				'code' => 'image_not_found',
			], Response::HTTP_NOT_FOUND);
		}

		try {
			$metadata = $aiVisionManager->generateMetadata($image);

			return new JsonResponse([
				'success' => true,
				'data' => $metadata,
			]);
		} catch (AiException $e) {
			return $aiExceptionResponseFactory->create(
				$e,
				'image_metadata',
			);
		}
	}
}
