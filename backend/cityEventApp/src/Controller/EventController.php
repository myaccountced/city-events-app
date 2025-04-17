<?php

namespace App\Controller;

use App\Entity\BookmarkedEvent;
use App\Entity\Category;
use App\Entity\Report;
use App\Entity\User;
use App\Enum\RecurringType;
use App\Repository\EventRepository;
use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Media;
use App\Entity\Event;
use App\Entity\EventInteraction;
use App\Enum\EventInteractionStatus;
use Doctrine\ORM\Query\Parameter;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EventController extends AbstractController
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $entityManager;
    private $jwtManager;
    private NotificationController $notificationController;
    private const SORT_PRECEDENCE = ['startDate', 'title', 'location'];

    public function __construct(EventRepository $eventRepository, JWTTokenManagerInterface $jwtManager, MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $this->eventRepository = $eventRepository;
        $this->jwtManager = $jwtManager;
        $this->entityManager = $entityManager;
        $this->notificationController = new NotificationController($entityManager, $mailer);
    }

    /***
     * To handle GET request, which contain optional filter or sorter
     * Pattern of the URL is:
     * GET http://127.0.0.1:8001/eventsWithFilterAndSorter?limit=20&offset=0&filter[moderatorApproval]=1&filter[eventCategory]=Food%20and%20Drink&filter[eventCategory]=Sports&sortField=eventStartDate&sortOrder=ASC
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/eventsWithFilterAndSorter', name: 'eventsAdvanced')]
    public function getEventsWithFilterAndSorter(Request $request, EventRepository $eventRepository): JsonResponse
    {
        date_default_timezone_set('Canada/Central');

        $limit = (int)$request->query->get('limit', 20);
        $offset = (int)$request->query->get('offset', 0);

        // Get sorting parameters (default to empty if not provided)
        $sortField = $request->query->get('sortField', '');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        // Extract filters (e.g. filter[category]=music&filter[category]=sports)
        $filters = $request->query->all('filter');

        //optional params
        $searchString = (string)$request->query->get('searchString', '');
        $isHistoric = (bool)$request->query->get('isHistoric', false);

        $events = $eventRepository->findActiveEventsWithFilterAndSorter($limit, $offset, $filters, $sortField, $sortOrder, $isHistoric, $searchString);

        $payload = [];

        foreach ($events as $event) {
            $media = $this->getEventMedia($this->entityManager, $event->getId());
            $bookmarks = $this->getEventBookmarks($this->entityManager, $event->getId());
            $interactions = $this->getEventInteractions($this->entityManager, $event->getId());

            $eventArray = $event->toArrayOfProperties();
            $eventArray['media'] = $media;
            $eventArray['bookmarks'] = $bookmarks;
            $eventArray['interactions'] = $interactions;
            $payload[] = $eventArray;
        }

        return new JsonResponse($payload);
    }

    /**
     * Creates a Response object containing a JSON representation of a single event
     * when the api is called.
     * @param int $eventID id of the Event
     * @return Response Response containing JSON Event object
     */
    #[Route('/event/{eventID}', name: 'app_event')]
    public function getEvent(int $eventID = 1): Response
    {
        try {
            $event = $this->eventRepository->getEventById($eventID);


            $eventArray = $event->toArrayOfProperties();
            $eventArray['media'] = $this->getEventMedia($this->entityManager, $event->getId());
            $eventArray['bookmarks'] = $this->getEventBookmarks($this->entityManager, $event->getId());
            $eventArray['interactions'] = $this->getEventInteractions($this->entityManager, $event->getId());
            return new JsonResponse($eventArray, Response::HTTP_OK);
        } catch (Exception $e) {
            print $e->getMessage();

            return new Response();
        }
    }

//    #[Route('/events/upload', name: 'upload', methods: ['POST'])]
    public function uploadImages(Request $request, Event $event, EntityManagerInterface $entityManager): array
    {
        $images = ['photoOne', 'photoTwo', 'photoThree'];
        $uploadedPaths = [];
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        $maxFileSize = 5 * 1024 * 1024; // 5 MB
        $errors = [];
        $imageCount = 0;
        $maxImages = 3; // Maximum number of images allowed
        $validImageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; // Add supported image MIME types


        // Start a transaction to ensure atomicity
        $entityManager->beginTransaction();

        try {
            $fileCount = 0;
            // Process and upload each image
            foreach ($request->files as $imageKey => $file) {
                $file = $request->files->get($imageKey);

                if ($fileCount >= $maxImages) {
                    $errors['general'] = "You can only upload a maximum of {$maxImages} images.";
                    break; // Stop processing after reaching the max image limit
                }
                if ($file) {
                    // Check if the file is a valid image MIME type
                    if (!in_array($file->getMimeType(), $validImageMimeTypes)) {
                        $errors[$imageKey] = "File is not a valid image.";
                        continue;
                    }

                    // Validate file size
                    if ($file->getSize() > $maxFileSize) {
                        $errors[$imageKey] = "File exceeds the size limit of 5MB.";
                        continue;
                    }

                    // Validate file is an image using MIME type
                    $imageInfo = @getimagesize($file->getPathname());
                    if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
                        $errors[$imageKey] = "File is not a valid image.";
                        continue;
                    }

                    // Generate a unique filename and move the file
                    $newFilename = uniqid() . '.' . $file->guessExtension();
                    $destinationPath = $uploadDir . '/' . $newFilename;
                    try {
//                    $file->move($uploadDir, $newFilename);
                        copy($file->getPathname(), $destinationPath);
                        $uploadedPaths[] = $newFilename;

                        // Create a new Media entity for each uploaded image
                        $media = new Media();
                        $media->setEvent($event); // Link the media to the event
                        $media->setPath($newFilename);

                        $entityManager->persist($media);
                        $imageCount++;
                        $fileCount++;
                    } catch (\Exception $e) {
                        $errors[$imageKey] = "Failed to upload: " . $e->getMessage();
                    }
                }
            }

            // If there are any errors, don't upload any images
            if (!empty($errors)) {
                throw new \Exception('Image upload failed.');
            }
            // If no errors, update the event's image count
            $event->setEventImages($imageCount);
//             Save the uploaded image paths to the database
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            $entityManager->persist($event);
            $entityManager->flush();

            // Commit the transaction after everything is successful
            $entityManager->commit();
            return [
                'imageCount' => $imageCount,
                'success' => empty($errors),
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            // Rollback the transaction to prevent any changes to the database or files
            $entityManager->rollback();

            // Optionally delete any files that were uploaded up until the error
            foreach ($uploadedPaths as $uploadedPath) {
                $filePath = $uploadDir . '/' . $uploadedPath;
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete uploaded file
                }
            }
            return [
                'imageCount' => 0,
                'success' => false,
                'errors' => $errors,
                'message' => 'Failed to save media or update event: ' . $e->getMessage()
            ];
        }
    }

    /** Route for posting an event or a series of events
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/events', name: 'post_event', methods: ['POST'])]
    public function postEvent(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->request->get('eventData'), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check if the user is banned
        if (empty($data['userId'])) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);
        } else {
            $user = $entityManager->getRepository(User::class)->find($data['userId']);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_BAD_REQUEST);
            }

            $bannedUser = $user->getBanned();
            if ($bannedUser) {
                return new JsonResponse(['error' => 'You are banned and cannot post events.'], Response::HTTP_FORBIDDEN);
            }
        }

        // Validate instance number
        if ($data['instanceNumber'] == null) {
            $instanceCount = 1;
        } elseif (($data['instanceNumber'] < 2 && $data['recurring']) || ($data['instanceNumber'] > 12 && $data['recurring'])) {
            return new JsonResponse(['errors' => 'Invalid number. Enter numbers between 2-12'], Response::HTTP_BAD_REQUEST);
        } else {
            $instanceCount = $data['instanceNumber'];
        }
        // Get the recurring type
        $recurringTypeValue = $data['eventRecurringType'] ?? null;
        $recurringType = $recurringTypeValue ? RecurringType::from($recurringTypeValue) : null;

        // Validate and parse start datetime
        if (!empty($data['eventStartDate']) && !empty($data['eventStartTime'])) {
            try {
                $startCarbon = Carbon::parse($data['eventStartDate'] . ' ' . $data['eventStartTime']);
            } catch (\Exception $e) {
                return new JsonResponse(['errors' => ['eventStartDate' => 'Use the calendar icon to enter a proper date.']], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return new JsonResponse(['errors' => ['eventStartDate' => 'You must enter a start date and time.']], Response::HTTP_BAD_REQUEST);
        }

        // Validate and parse end datetime (or fallback)
        if (!empty($data['eventEndDate']) && !empty($data['eventEndTime'])) {
            try {
                $endCarbon = Carbon::parse($data['eventEndDate'] . ' ' . $data['eventEndTime']);
            } catch (\Exception $e) {
                return new JsonResponse(['errors' => ['eventEndDate' => 'Use the calendar icon to enter a proper date.']], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $endCarbon = $startCarbon->copy(); // Fallback to start datetime
        }

        $baseEventID = null; // ID of the parent event of the series

        for ($i = 0; $i < $instanceCount; $i++) {
            // Create Event object and persist
            $event = new Event();
            $event->setEventTitle($data['eventTitle']);
            $event->setEventDescription($data['eventDescription']);
            $event->setEventLocation($data['eventLocation']);
            $event->setEventAudience($data['eventAudience']);

            $allCats = explode(", ", $data['eventCategory']);
            foreach ($allCats as $category) {
                $eventCategory = new Category();
                $eventCategory->setCategoryName($category);
                $event->addCategory($eventCategory);
            }

            // Set start and end dates with fallback already handled
            $event->setEventStartDate($startCarbon->copy());
            $event->setEventEndDate($endCarbon->copy());

            // Optional fields
            $event->setEventLink($data['eventLink'] ?? null);
            $event->setModeratorApproval(false);
            $event->setEventCreator($data['creator'] ?? 'Anonymous');
            $event->setUserId($user);

            // Set the Recurring type
            if ($recurringType) {
                $event->setEventRecurringType($recurringType);
            }
            // Set the parentEventID of the child events to the ID of the parent event
            if ($i > 0 && $baseEventID) {
                $event->setParentEventID($baseEventID);
            }

            $errors = $validator->validate($event);
            // Handle file uploads
            $uploadResult = $this->uploadImages($request, $event, $entityManager);

            if (!$uploadResult['success']) {
                return new JsonResponse(['errors' => $uploadResult['errors']], Response::HTTP_BAD_REQUEST);
            }

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $imageCount = $uploadResult['imageCount'];
            $event->setEventImages(json_encode($imageCount, JSON_UNESCAPED_SLASHES));

            // Persist and save event
            $entityManager->persist($event);
            $entityManager->flush();

            // Set the base (parent event)
            if ($i === 0) {
                $baseEventID = $event->getId();
            }

            // Increment start/end date if recurring
            if ($data['recurring']) {
                match ($recurringType) {
                    RecurringType::WEEKLY => $startCarbon->addWeek(),
                    RecurringType::BI_WEEKLY => $startCarbon->addWeeks(2),
                    RecurringType::MONTHLY => $startCarbon->addMonthsWithNoOverflow(),
                    default => $startCarbon,
                };

                match ($recurringType) {
                    RecurringType::WEEKLY => $endCarbon->addWeek(),
                    RecurringType::BI_WEEKLY => $endCarbon->addWeeks(2),
                    RecurringType::MONTHLY => $endCarbon->addMonthsWithNoOverflow(),
                    default => $endCarbon,
                };
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Event created successfully',
        ], Response::HTTP_CREATED);
    }


    #[Route('/events/mod', name: 'event_status', methods: ['PUT'])]
    public function updateModApproval(
        Request                $req,
        EntityManagerInterface $em,
        TokenStorageInterface  $tokenStorage,
        Security               $security
    ): JsonResponse
    {
        // Get the authenticated user
        $token = $tokenStorage->getToken();
        $user = $token?->getUser();

        // Ensure the user is authenticated and has moderator role
        if (!$user || !$security->isGranted('ROLE_MODERATOR')) {
            return new JsonResponse(["error" => "Permission Denied"], Response::HTTP_UNAUTHORIZED);
        }

        // Decode JSON request body
        $data = json_decode($req->getContent(), false);
        if (!$data || !isset($data->id, $data->status)) {
            return new JsonResponse(["error" => "Invalid request data"], Response::HTTP_BAD_REQUEST);
        }

        // Fetch event by ID
        $event = $this->eventRepository->find($data->id);
        if (!$event) {
            return new JsonResponse(["error" => "Event not found"], Response::HTTP_NOT_FOUND);
        }

        // Update approval status
        if ($data->status) {
            if (!$event->isModeratorApproval()) {
                // It was not already approved!
                $event->setModeratorApproval(true);
                $em->flush();

                // Sending out notifications upon event approval!
                $this->notificationController->checkForUpdateNotifications($event);
            }

            return new JsonResponse(json_decode($this->getEvent($event->getId())->getContent()), Response::HTTP_OK);
        } else {
            // Rejecting: delete the event
            $em->remove($event);

            // Rejecting: delete the images related to the event
            $mediaRepository = $em->getRepository(Media::class);
            $mediaList = $mediaRepository->findBy(['event' => $event]);
            foreach ($mediaList as $media) {
                $em->remove($media);
            }

            $em->flush(); // Save the changes
            return new JsonResponse(["message" => "Event deleted successfully"], Response::HTTP_OK);
        }
    }

    #[Route('/myevents', name: 'view_user_events', methods: ['GET'])]
    public function getUserEvents(Request $req, EntityManagerInterface $entityManager, Security $security): JsonResponse
    {
        date_default_timezone_set('Canada/Central');

        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $username = $req->query->get('user');

        try {
            $repository = $entityManager->getRepository(Event::class);
            $now = new \DateTime();
            $now->setTime(0, 0, 0);

            //get future events
            $queryBuilderFuture = $repository->createQueryBuilder('e')
                ->where('e.eventCreator = :username')
                ->andWhere('e.eventStartDate >= :now')
                ->setParameters(new ArrayCollection([
                    new Parameter('username', $username),
                    new Parameter('now', $now)
                ]))
                ->orderBy('e.eventStartDate', 'ASC')
                ->addOrderBy('e.eventTitle', 'ASC');

            $futureEvents = $queryBuilderFuture->getQuery()->getResult();

            //get past events
            $queryBuilderPast = $repository->createQueryBuilder('e')
                ->where('e.eventCreator = :username')
                ->andWhere('e.eventStartDate < :now')
                ->setParameters(new ArrayCollection([
                    new Parameter('username', $username),
                    new Parameter('now', $now)
                ]))
                ->orderBy('e.eventStartDate', 'DESC')
                ->addOrderBy('e.eventTitle', 'ASC');

            $pastEvents = $queryBuilderPast->getQuery()->getResult();

            // Story 60 new way
            $futurePayload = [];
            foreach ($futureEvents as $event) {
                $media = $this->getEventMedia($this->entityManager, $event->getId());
                $bookmarks = $this->getEventBookmarks($this->entityManager, $event->getId());
                $interactions = $this->getEventInteractions($this->entityManager, $event->getId());

                $eventArray = $event->toArrayOfProperties();
                $eventArray['media'] = $media;
                $eventArray['bookmarks'] = $bookmarks;
                $eventArray['interactions'] = $interactions;

                $futurePayload[] = $eventArray;
            }

            // Story 60 new way
            $pastPayload = [];
            foreach ($pastEvents as $event) {
                $media = $this->getEventMedia($this->entityManager, $event->getId());
                $bookmarks = $this->getEventBookmarks($this->entityManager, $event->getId());
                $interactions = $this->getEventInteractions($this->entityManager, $event->getId());

                $eventArray = $event->toArrayOfProperties();
                $eventArray['media'] = $media;
                $eventArray['bookmarks'] = $bookmarks;
                $eventArray['interactions'] = $interactions;

                $pastPayload[] = $eventArray;
            }

            $returnPayload = [$futurePayload, $pastPayload];

            return new JsonResponse($returnPayload);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred while fetching events: ' . $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    // returns an array of strings of image filenames
    private function getEventMedia(EntityManagerInterface $entityManager, int $eventId): array
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

    // returns an array with EventInteractions recoding counter of interest and attending to an event
    // as well as user's status of interest or attending
    public function getEventInteractions(EntityManagerInterface $entityManager, int $eventId): array
    {
        $repository = $entityManager->getRepository(EventInteraction::class);
        $event = $entityManager->getRepository(Event::class)->findBy(['id' => $eventId]);

        if (!$event) {
            return [
                'interestedCount' => 0,
                'attendingCount' => 0,
                'userInteractions' => []
            ];
        }

        $interactions = $repository->findBy(['event' => $event]);
        $interestedCount = 0;
        $attendingCount = 0;
        $userInteractions = [];

        foreach ($interactions as $interaction) {
            $status = $interaction->getStatus();

            if ($status === EventInteractionStatus::INTERESTED) {
                $interestedCount++;
            } elseif ($status === EventInteractionStatus::ATTENDING) {
                $attendingCount++;
            }

            $userInteractions[] = [
                'userId' => $interaction->getUser()->getId(),
                'status' => $interaction->getStatus()->value
            ];
        }

        return [
            'interestedCount' => $interestedCount,
            'attendingCount' => $attendingCount,
            'userInteractions' => $userInteractions
        ];
    }

    /**
     * Toggle interest in an event
     */
    #[Route('/events/interactions/interest', name: 'toggle_interest', methods: ['POST', 'DELETE'])]
    public function toggleInterest(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventId = $data['eventID'] ?? null;
        $userId = $data['userID'] ?? null;

        if (!$eventId || !$userId) {
            return $this->json(['error' => 'Missing eventID or userID'], Response::HTTP_BAD_REQUEST);
        }

        $event = $entityManager->getRepository(Event::class)->find($eventId);
        $userRepo = $entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => $userId]);

        if (!$event) {
            return $this->json(['error' => 'Event  not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$user) {
            return $this->json(['error' => 'User  not found'], Response::HTTP_NOT_FOUND);
        }


        $repository = $entityManager->getRepository(EventInteraction::class);
        $interaction = $repository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Handle interest toggling
        if ($request->getMethod() === 'POST') {
            // If interaction doesn't exist yet, create it with INTERESTED status
            if (!$interaction) {
                $interaction = new EventInteraction();
                $interaction->setEvent($event);
                $interaction->setUser($user);
                $interaction->setStatus(EventInteractionStatus::INTERESTED);
                $entityManager->persist($interaction);
            } // If user was attending, change to interested
            elseif ($interaction->getStatus() === EventInteractionStatus::ATTENDING) {
                $interaction->setStatus(EventInteractionStatus::INTERESTED);
            } // If user already had INTERESTED status, toggle it off by setting NO_INTERACTION
            elseif ($interaction->getStatus() === EventInteractionStatus::INTERESTED) {
                $interaction->setStatus(EventInteractionStatus::NO_INTERACTION);
                $entityManager->remove($interaction);
            } // If user had NO_INTERACTION status, set to INTERESTED
            else {
                $interaction->setStatus(EventInteractionStatus::INTERESTED);
                $entityManager->persist($interaction);
            }
        } else { // DELETE
            // Check if interaction exists before trying to access its status
            if ($interaction) {
                // If status is INTERESTED, set to NO_INTERACTION and possibly remove
                if ($interaction->getStatus() === EventInteractionStatus::INTERESTED) {
                    $interaction->setStatus(EventInteractionStatus::NO_INTERACTION);
                    $entityManager->remove($interaction);
                }
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }
    /**
     * Toggle attendance for an event
     */
    #[Route('/events/interactions/attendance', name: 'toggle_attendance', methods: ['POST', 'DELETE'])]
    public function toggleAttendance(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventId = $data['eventID'] ?? null;
        $userId = $data['userID'] ?? null;

        if (!$eventId || !$userId) {
            return $this->json(['error' => 'Missing eventID or userID'], Response::HTTP_BAD_REQUEST);
        }

        $event = $entityManager->getRepository(Event::class)->find($eventId);
        $userRepo = $entityManager->getRepository(User::class);
        $user = $userRepo->findOneBy(['username' => $userId]);

        if (!$event || !$user) {
            return $this->json(['error' => 'Event or User not found'], Response::HTTP_NOT_FOUND);
        }

        $repository = $entityManager->getRepository(EventInteraction::class);
        $interaction = $repository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        // Handle attending toggling
        if ($request->getMethod() === 'POST') {
            // If interaction doesn't exist yet, create it with ATTENDING status
            if (!$interaction) {
                $interaction = new EventInteraction();
                $interaction->setEvent($event);
                $interaction->setUser($user);
                $interaction->setStatus(EventInteractionStatus::ATTENDING);
                $entityManager->persist($interaction);
            }
            // If user was interested, change to attending
            elseif ($interaction->getStatus() === EventInteractionStatus::INTERESTED) {
                $interaction->setStatus(EventInteractionStatus::ATTENDING);
            }
            // If user already had ATTENDING status, toggle it off by setting NO_INTERACTION
            elseif ($interaction->getStatus() === EventInteractionStatus::ATTENDING) {
                $interaction->setStatus(EventInteractionStatus::NO_INTERACTION);
                $entityManager->remove($interaction);
            }
            // If user had NO_INTERACTION status, set to ATTENDING
            else {
                $interaction->setStatus(EventInteractionStatus::ATTENDING);
                $entityManager->persist($interaction);
            }
        } else { // DELETE
            // Check if interaction exists before trying to access its status
            if ($interaction) {
                // If status is ATTENDING, set to NO_INTERACTION and possibly remove
                if ($interaction->getStatus() === EventInteractionStatus::ATTENDING) {
                    $interaction->setStatus(EventInteractionStatus::NO_INTERACTION);
                    $entityManager->remove($interaction);
                }
            }
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Delete an event, an event in a series, or the whole series
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/events/delete', name: 'delete_event', methods: ['DELETE'])]
    public function deleteEvents(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Extract the request data
        $data = json_decode($request->getContent(), true);
        $eventData = $data['eventData'] ?? null;

        // Validate the request data
        if (!$eventData || !isset($eventData['eventID'], $eventData['deleteSeries'])) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        // Find the event being processed using the ID
        $event = $this->eventRepository->getEventByID($eventData['eventID']);
        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], 404);
        }

        $deleteSeries = $eventData['deleteSeries'];
        $now = new \DateTime();

        // Set up the repositories needed to manipulate the tables in the DB
        $mediaRepository = $entityManager->getRepository(Media::class);
        $reportRepository = $entityManager->getRepository(Report::class);
        $bookmarkedEventRepository = $entityManager->getRepository(BookmarkedEvent::class);

        if ($deleteSeries) { // If we are posting a series of events
            // Handle if the event is a parent event
            if ($event->getParentEventID() == null) {
                $parentEventIDToFind = $event->getId();
            }
            else {
                $parentEventIDToFind = $event->getParentEventID();
            }

            if ($event->getEventEndDate() < $now) {
                // It's a past event: delete entire series (past + future)
                $series = $this->eventRepository->findEventSeries($parentEventIDToFind);
                $message = "Series deleted (past and future).";
            } else {
                // It's a future or current event: delete future events only
                $series = $this->eventRepository->findEventSeriesUpcomingOnly($parentEventIDToFind);
                $message = "Series deleted (future only).";
            }

            // Process the events that will be deleted one by one
            foreach ($series as $eventItem) {
                // Delete the related media
                $mediaItems = $mediaRepository->findBy(['event' => $eventItem]);
                foreach ($mediaItems as $mediaItem) {
                    $entityManager->remove($mediaItem);
                }
                // Delete the related reports
                $reportItems = $reportRepository->findBy(['eventID' => $eventItem->getId()]);
                foreach ($reportItems as $reportItem) {
                    $entityManager->remove($reportItem);
                }
                // Delete the related bookmarks
                $bookmarkedEventItems = $bookmarkedEventRepository->findBy(['event' => $eventItem]);
                foreach ($bookmarkedEventItems as $bookmarkedEventItem) {
                    $entityManager->remove($bookmarkedEventItem);
                }
                // Delete the event
                $entityManager->remove($eventItem);
            }

            $entityManager->flush();
            return new JsonResponse(['status' => $message], 200);
        } else { // If we are posting a single event
            // Delete the related media
            $mediaItems = $mediaRepository->findBy(['event' => $event]);
            foreach ($mediaItems as $mediaItem) {
                $entityManager->remove($mediaItem);
            }
            // Delete the related reports
            $reportItems = $reportRepository->findBy(['eventID' => $event->getId()]);
            foreach ($reportItems as $reportItem) {
                $entityManager->remove($reportItem);
            }
            // Delete the related bookmarks
            $bookmarkedEventItems = $bookmarkedEventRepository->findBy(['event' => $event]);
            foreach ($bookmarkedEventItems as $bookmarkedEventItem) {
                $entityManager->remove($bookmarkedEventItem);
            }

            // If the event being deleted is a parent event, reassign the child events' parentEvent to the next child event inline
            if ($event->getParentEventID() === null) {
                $this->reassignChildEventsParent($event, $entityManager);
            }

            // Delete only this specific instance
            $entityManager->remove($event);
            $entityManager->flush();
            return new JsonResponse(['status' => 'Single event deleted'], 200);
        }
    }

    /**
     * Reassign child events' parentEvent to the next child event in line.
     * @param Event $parentEvent The event being deleted (parent event)
     * @param EntityManagerInterface $entityManager
     */
    private function reassignChildEventsParent(Event $parentEvent, EntityManagerInterface $entityManager): void
    {
        // Fetch child events of the parent event
        $childEvents = $this->eventRepository->findBy(['parentEventID' => $parentEvent]);

        if (count($childEvents) > 1) {
            // Get the next child event's id in the series
            $nextChildEvent = $childEvents[1];
            $nextChildEventID = $nextChildEvent->getId();

            foreach ($childEvents as $childEvent) {
                // Update the parentEventID of the child to the next child event's ID
                if ($childEvent !== $nextChildEvent) {
                    $childEvent->setParentEventID($nextChildEventID);
                    $entityManager->persist($childEvent);
                }
                else {
                    // The new child event's parentEventID would be null
                    $childEvent->setParentEventID(null);
                    $entityManager->persist($childEvent);
                }
            }

            $entityManager->flush();
        }
    }

    //region Hayden's story49
    #[Route('/myevents/{eventId}', name: 'update_user_events', methods: ['POST', 'DELETE'])]
    public function updateUserEvents(
        Request                $req,
        EntityManagerInterface $em,
        TokenStorageInterface  $tokenStorage,
        int                    $eventId,
        ValidatorInterface     $validator
    ): JsonResponse
    {
        // Get the authenticated user
        $token = $tokenStorage->getToken();
        $user = $token?->getUser();

        // Find the event to update
        $event = $em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            return new JsonResponse(['error' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the user owns this event or is an admin
        if ($event->getUserId() !== $user) {
            return new JsonResponse(['error' => 'You do not have permission to update this event'], Response::HTTP_FORBIDDEN);
        }

        // Check if the user is banned
        $bannedUser = $user->getBanned();
        if ($bannedUser) {
            return new JsonResponse(['error' => 'You are banned and cannot update events.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($req->request->get('eventData'), true);

        // Check if this is a delete request
        if ($req->getMethod() == 'DELETE') {
            // Delete the event
            $em->remove($event);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Event deleted successfully'
            ], Response::HTTP_OK);
        }

        if (!$data) {
            error_log('Failed to parse data from any source');
            return new JsonResponse([
                'error' => 'Invalid or missing JSON data',
                'eventId' => $event->getId(),
            ], Response::HTTP_BAD_REQUEST);
        }


        // Continue with update logic if not deleting
        if (isset($data['eventTitle'])) {
            $event->setEventTitle($data['eventTitle']);
        }

        if (isset($data['eventDescription'])) {
            $event->setEventDescription($data['eventDescription']);
        }

        if (isset($data['eventLocation'])) {
            $event->setEventLocation($data['eventLocation']);
        }

        if (isset($data['eventAudience'])) {
            $event->setEventAudience($data['eventAudience']);
        }

        if (isset($data['eventCategory'])) {
            // Get the new category names as an array
            $newCategoryNames = explode(", ", $data['eventCategory']);

            // Create a map of existing category names for easy lookup
            $existingCategoryMap = [];
            foreach ($event->getCategories() as $existingCategory) {
                $existingCategoryMap[$existingCategory->getCategoryName()] = $existingCategory;
            }

            // Find categories to remove (exist in current but not in new)
            $categoriesToRemove = [];
            foreach ($existingCategoryMap as $categoryName => $category) {
                if (!in_array($categoryName, $newCategoryNames)) {
                    $categoriesToRemove[] = $category;
                }
            }

            // Find categories to add (exist in new but not in current)
            $categoriesToAdd = [];
            foreach ($newCategoryNames as $newCategoryName) {
                if (!isset($existingCategoryMap[$newCategoryName])) {
                    $categoriesToAdd[] = $newCategoryName;
                }
            }

            // Add new categories first
            foreach ($categoriesToAdd as $categoryName) {
                $eventCategory = new Category();
                $eventCategory->setCategoryName($categoryName);
                $eventCategory->setEvent($event);
                $event->addCategory($eventCategory);
                $em->persist($eventCategory);
            }

            // Remove categories that should be removed
            foreach ($categoriesToRemove as $category) {
                $event->removeCategory($category);
                $em->remove($category);
            }
        }

        if (isset($data['eventLink'])) {
            $event->setEventLink($data['eventLink']);
        }

        // Update start date and time if provided
        if (!empty($data['eventStartDate']) && !empty($data['eventStartTime'])) {
            try {
                $startDateTimeString = $data['eventStartDate'] . ' ' . $data['eventStartTime'];
                $startDateTime = new \DateTime($startDateTimeString);
                $event->setEventStartDate($startDateTime);
            } catch (\Exception $e) {
                return new JsonResponse(['errors' => ['eventStartDate' => 'Use the calendar icon to enter a proper date.']], Response::HTTP_BAD_REQUEST);
            }
        }

        // Update end date and time if provided
        if (!empty($data['eventEndDate']) && !empty($data['eventEndTime'])) {
            try {
                $endDateTimeString = $data['eventEndDate'] . ' ' . $data['eventEndTime'];
                $endDateTime = new \DateTime($endDateTimeString);
                $event->setEventEndDate($endDateTime);
            } catch (\Exception $e) {
                return new JsonResponse(['errors' => ['eventEndDate' => 'Use the calendar icon to enter a proper date.']], Response::HTTP_BAD_REQUEST);
            }
        }

        // Validate the updated event
        $errors = $validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Handle file uploads

        if ($req->files->count() > 0) {
            // If new files are being uploaded, purge existing media
            $mediaEntities = $event->getMedia();

            foreach ($mediaEntities as $media) {
                // Get the full file path
                $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $media->getPath();


                // Check if the file exists before attempting to delete it
                if (file_exists($filePath)) {
                    try {
                        unlink($filePath);
                        // Optionally log successful deletion
                        error_log('Successfully deleted file: ' . $filePath);
                    } catch (\Exception $e) {
                        // Log the error but continue with database removal
                        error_log('Failed to delete file: ' . $filePath . ' - Error: ' . $e->getMessage());
                    }
                } else {
                    // File doesn't exist, log this information
                    error_log('File not found for deletion: ' . $filePath);
                }

                // Remove the association and delete the database record
                $event->removeMedia($media);
                $em->remove($media);
            }

            // Flush changes to ensure media records are deleted before adding new ones
            $em->flush();
        }

        $uploadResult = $this->uploadImages($req, $event, $em);

        if (!$uploadResult['success']) {
            return new JsonResponse(['errors' => $uploadResult['errors']], Response::HTTP_BAD_REQUEST);
        }

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $imageCount = $uploadResult['imageCount'];
        $event->setEventImages(json_encode($imageCount, JSON_UNESCAPED_SLASHES));

        //ALL updated events must be sent for moderator approval
        $event->setModeratorApproval(false);
        //set modification date to today
        $event->setModificationDate(new \DateTime());

        // Save the updated event
        $em->persist($event);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Event updated successfully',
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getEventTitle(),
                'description' => $event->getEventDescription(),
            ]
        ], Response::HTTP_OK);
    }
    //endregion
}