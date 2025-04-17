<?php

namespace App\Controller;

use App\Entity\Report;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ReportController extends AbstractController
{
    private ValidatorInterface $validator;
    private EventRepository $eventRepository;
    public function __construct(ValidatorInterface $validator, EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
        $this->validator=$validator;
    }

    #[Route('/api/reports', name: 'create_report', methods: ['POST'])]
    public function createReports(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Extract data from POST request
        $eventID = $data['eventID']?? '';
        $reportReason = $data['reason']??'';

        // Validate eventID
        if (empty($eventID) || !is_numeric($eventID)) {
            return new JsonResponse([
                'message' => 'EventID is required and must be an integer'],
            Response::HTTP_BAD_REQUEST);
        }

        // Validate reason
        /*if (empty($reportReason) || !is_string($reportReason)) {
            return new JsonResponse([
                'message' => 'Reason is required and must be a non-empty string'],
            Response::HTTP_BAD_REQUEST);
        }*/

        $report = new Report();
        $report->setEventID($eventID);
        $report->setReason(trim($reportReason));

        // Validate the report entity based on validation annotation
        $errors = $this->validator->validate($report);
        // If there are validation errors, return them in the response
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();  // Set field as key and message as value
            }
            return new JsonResponse([
                'message' => 'Invalid data',
                'errors'=>$errorMessages],
                Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        try {
            $em->persist($report);
            $em->flush();

            // Set report count to add one
            $event = $this->eventRepository->getEventByID($eventID);
            $event->setReportCount($event->getReportCount() + 1);
            $em->persist($event);
            $em->flush();

            return new JsonResponse([
                'message'=>'Report created successfully',
            ], Response::HTTP_CREATED);
        }
        catch (\Exception) {
            return new JsonResponse([
                'message' => 'Database error'],
                Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Return an array of events that has been reported three times.
     * @param Request $request
     * Limit: The maximum amount of events we can fetch
     * Offset: Starting point for the query results. Example if we have 20 for offset, we will return 21st result onwards.
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/get_reported_events', name: 'get_report_details', methods: ['POST'])]
    public function getReportedEvents(Request $request, EntityManagerInterface $em): JsonResponse
    {
        date_default_timezone_set('Canada/Central');
        $todayDate = new \DateTime();
        $data = json_decode($request->getContent(), true);

        // Set the limit and offset
        $limit = $data['limit'] ?? null;
        $offset = $data['offset'] ?? null;

        if (!isset($limit) || !isset($offset) || $limit <= 0 || $offset < 0) {
            return new JsonResponse(['message' => 'Limit and offset are required'], Response::HTTP_BAD_REQUEST);
        }

        // Find the events reported three times
        $events = $this->eventRepository->findReportedEvents($limit, $offset);
        $payload = [];
        // Convert them to JSON
        foreach ($events as $event) {
            $eventStartDate = $event->getEventStartDate();

            if ($eventStartDate->format('U') > $todayDate->format('U'))
            {
//                $payload[] = [
//                    'id' => $event->getId(),
//                    'title' => $event->getEventTitle(),
//                    'description' => $event->getEventDescription(),
//                    'startDate' => $event->getEventStartDate()->format('Y-m-d H:i'),
//                    'endDate' => $event->getEventEndDate()->format('Y-m-d H:i'),
//                    'location' => $event->getEventLocation(),
//                    'audience' => $event->getEventAudience(),
//                    'category' => $event->getEventCategory(),
//                    'creator' => $event->getEventCreator(),
//                    'images' => $event->getEventImages(),
//                    'links' => $event->getEventLink()
//                ];
                $payload[] = $event->toArrayOfProperties();
            }
        }
        return new JsonResponse($payload);
    }

    /** Get the report instances of a specific event using eventID
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/get_reports', name: 'get_reports_by_event', methods: ['POST'])]
    public function getReportsByEventId(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventID = $data['eventID'] ?? null;

        if (empty($eventID) || !is_numeric($eventID)) {
            return new JsonResponse(['message' => 'EventID is required and must be a valid integer',], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Fetch the event by ID
        $event = $this->eventRepository->find($eventID);

        if (!$event) {
            return new JsonResponse(['message' => 'Event not found',], JsonResponse::HTTP_NOT_FOUND);
        }

        // Fetch all reports related to this event
        $reports = $em->getRepository(Report::class)->findBy(['eventID' => $eventID]);
        $reportInstances = [];

        foreach ($reports as $report) {
            $reportInstances[] = [
                'reportId' => $report->getId(),
                'reportDate' => $report->getReportDate()->format('Y-m-d'),
                'reportTime' => $report->getReportTime()->format('H:i'),
                'reason' => $report->getReason(),
            ];
        }

        return new JsonResponse([
            'eventID' => $eventID,
            'reportedInstances' => $reportInstances,
        ]);
    }

    /** Clear the reports related to the event and reset the reportCount of that event.
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route('/api/clear_reports', name: 'clear_reports', methods: ['DELETE'])]
    public function clearReports(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $eventID = $data['eventID'] ?? null;

        // Validate the eventID
        if (empty($eventID) || !is_numeric($eventID)) {
            return new JsonResponse(['message' => 'EventID is required and must be a valid integer'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Check if the event with that ID exists
        $event = $this->eventRepository->find($eventID);
        if (!$event) {
            return new JsonResponse(['message' => 'Event not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Fetch all reports associated with this eventID
        $reports = $em->getRepository(Report::class)->findBy(['eventID' => $eventID]);

        if (empty($reports)) {
            return new JsonResponse(['message' => 'No reports found for this event'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Delete the reports
        foreach ($reports as $report) {
            $em->remove($report);
        }

        // Reset the report count on the event entity
        $event->clearReportCount();

        // Persist the event entity with the updated report count
        $em->persist($event);

        // Save changes
        $em->flush();

        // Return success response
        return new JsonResponse(['message' => 'Reports successfully cleared and report count reset for event ' . $eventID]);
    }
}