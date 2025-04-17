<?php

namespace App\Controller;

use App\Entity\BookmarkedEvent;
use App\Entity\Event;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\BookmarkedEventRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Cache\Exception\FeatureNotImplemented;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class BookmarkedEventController extends AbstractController
{
    private BookmarkedEventRepository $bookmarkedEventRepository;


    public function __construct(BookmarkedEventRepository $bookmarkedEventRepository, EntityManagerInterface $entityManager)
    {
        $this->bookmarkedEventRepository = $bookmarkedEventRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/events/bookmarks/user', name: 'bookmarks')]
    public function getBookmarkedEventsForAUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        date_default_timezone_set('Canada/Central');

        // Retrieve limit and offset for pagination
        $limit = (int)$request->query->get('limit', 20);
        $offset = (int)$request->query->get('offset', 0);

        $currentUsername = $request->query->get('currentUser');
        $userRepo = $entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => $currentUsername]);

        $bookmarkedRepo = $entityManager->getRepository(BookmarkedEvent::class);

        $bookmarkedevents = $bookmarkedRepo->findCurrentBookmarkedEvents($limit, $offset, $user);

        $events = [];

        foreach ($bookmarkedevents as $bookmark) {
            $events[] = $bookmark->getEvent();
        }

        $payload = [];

        foreach ($events as $event) {
            $media = $this->getEventMedia($this->entityManager, $event->getId());
            $bookmarks = $this->getEventBookmarks($this->entityManager, $event->getId());

            $eventArray = $event->toArrayOfProperties();
            $eventArray['media'] = $media;
            $eventArray['bookmarks'] = $bookmarks;
            $payload[] = $eventArray;
        }

        return new JsonResponse($payload);
    }

    #[Route('/events/bookmarks', name: 'isBookmarked')]
    public function isEventBookmarked(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {

            $currentUserName = (string)$request->query->get('currentUser');
            $eventID = (int)$request->query->get('eventID');

            // get the userid
            $userRepo = $entityManager->getRepository(User::class);
            $user = $userRepo->findOneBy(['username' => $currentUserName]);

            $eventRepo = $entityManager->getRepository(Event::class);
            $event = $eventRepo->findOneBy(['id' => $eventID]);

            // check if this specific event is bookmarked by this specific user
            $bookmarksRepo = $entityManager->getRepository(BookmarkedEvent::class);
            $bookmark = $bookmarksRepo->findOneBy(['event' => $event, 'user' => $user]);

            $isBookmarked = $bookmark != null;

            return new JsonResponse(['isBookmarked' => $isBookmarked], Response::HTTP_OK);
        }
        catch (Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/events/bookmarks', name: 'create_bookmark', methods: ['POST'])]
    public function createBookmarkedEvent(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate input
            $currentUser = $data['currentUser'] ?? null;
            $eventID = $data['eventID'] ?? null;

            // get the userid
            $userRepo = $entityManager->getRepository(User::class);
            $user = $userRepo->findOneBy(['username' => $currentUser]);

            $eventRepo = $entityManager->getRepository(Event::class);
            $event = $eventRepo->findOneBy(['id' => $eventID]);

            $bookmark = new BookmarkedEvent();
            $bookmark->setEvent($event);
            $bookmark->setUser($user);

            $entityManager->persist($bookmark);
            $entityManager->flush();
        }
        catch (Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Event was successfully bookmarked.',
        ], Response::HTTP_CREATED);

    }

    #[Route('/events/bookmarks', name: 'remove_bookmark', methods: ['DELETE'])]
    public function removeBookmarkedEvent(Request $request, EntityManagerInterface $entityManager) :JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate input
            $currentUser = $data['currentUser'] ?? null;
            $eventID = $data['eventID'] ?? null;

            // get the userid
            $userRepo = $entityManager->getRepository(User::class);
            $user = $userRepo->findOneBy(['username' => $currentUser]);

            $eventRepo = $entityManager->getRepository(Event::class);
            $event = $eventRepo->findOneBy(['id' => $eventID]);

            $bookmarkRepo = $entityManager->getRepository(BookmarkedEvent::class);
            $bookmark = $bookmarkRepo->findOneBy(['event' => $event, 'user' => $user]);

            $entityManager->remove($bookmark);
            $entityManager->flush();
        }
        catch (Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Event was successfully unbookmarked.',
        ], Response::HTTP_CREATED);
    }

    private function getEventMedia(EntityManagerInterface $entityManager, int $eventId) : array
    {
        $mediaRepository = $entityManager->getRepository(Media::class);
        $mediaList = $mediaRepository->findBy(['event' => $eventId]);
        $mediaPaths = [];
        foreach ($mediaList as $media) {
            $mediaPaths[] = $media->getPath();
        }

        return $mediaPaths;
    }

    // returns an array with the userids of people who have event bookmarked
    private function getEventBookmarks(EntityManagerInterface $entityManager, int $eventId): array
    {
        $bookmarkRepository = $entityManager->getRepository(BookmarkedEvent::class);
        $bookmarks = $bookmarkRepository->findBy(['event' => $eventId]);

        $bookmarkData = [];
        foreach ($bookmarks as $bookmark) {
            $bookmarkData[] = [
                'userId' => $bookmark->getUser()->getId(),
            ];
        }

        return $bookmarkData;
    }
}