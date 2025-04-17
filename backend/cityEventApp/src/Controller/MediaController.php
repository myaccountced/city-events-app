<?php

namespace App\Controller;


use App\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class MediaController extends AbstractController
{
    #[Route('/events/media', name: 'event_media')]
    function getEventImages(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $eventID = (int)$request->query->get('eventID');
        //$data = json_decode($request->getContent(), true);
        //$eventID = $data['id'];

        $repo = $entityManager->getRepository(Media::class);

        // get an array of the media entities for the event
        $images = $repo->findBy(['event' => $eventID]);

        $paths[] = [];
        foreach ($images as $image) {
            $paths[] = $image->getPath();
        }
        // get rid of the first element, which is just garbage from initializing the array
        array_shift($paths);

        // send an array of the image paths
        return new JsonResponse(['images' => $paths], Response::HTTP_OK);

    }

}