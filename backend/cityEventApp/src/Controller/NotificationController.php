<?php

namespace App\Controller;

use App\Entity\BookmarkedEvent;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationController extends AbstractController
{
    private EntityManagerInterface $em;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer)
    {
        $this->em = $entityManager;
        $this->mailer = $mailer;
    }


    /**
     * Updates users who have bookmarked the given Event, and users who are
     * following the given Event's creator.
     * @param Event $event Event that is causing the notification
     * @return void
     */
    public function checkForUpdateNotifications(Event $event): void
    {
        $poster = $event->getUserId();

        $users = $poster->getFollowers();
        $ids = [];

        // Notify each Follower
        foreach ($users as $notifyUser)
        {
            // Sending the email
            $this->sendEmail($event, $notifyUser, $this->mailer);

            // Add them to the list of IDs that have already been notified
            $ids[] = $notifyUser->getId();
        }

        $bookmarkRepo = $this->em->getRepository(BookmarkedEvent::class);

        // Finding all bookmarks for this event
        $bookmarks = $bookmarkRepo->findBy(['event' => $event->getId()]);

        // Notify Bookmark-ers that are not yet notified
        foreach ($bookmarks as $bookmark)
        {
            $u = $bookmark->getUser();
            if (!in_array($u->getId(), $ids))
            {
                // This user has yet to be notified
                $this->sendEmail($event, $u, $this->mailer);
            }
        }

    }


    /**
     * Sends an email to the given User about the given Event.
     * @param Event $event Event that is the topic of the email
     * @param User $user User to receive the email
     * @param MailerInterface $mailer Mailing transport
     * @return void
     */
    public function sendEmail(Event $event, User $user, MailerInterface $mailer): void
    {
        // Event information needed
        $eventObject = [
            "id" => $event->getId(),
            "title" => $event->getEventTitle(),
            "description" => $event->getEventDescription(),
            "location" => $event->getEventLocation(),
            "dateString" => $event->getEventStartDate()->format('Y-m-d'),
            "timeString" => $event->getEventStartDate()->format('H:i'),
            "categories" => "",
            "audience" => $event->getEventAudience(),
        ];

        // Adding the end date if it is different from the start date
        if ($event->getEventEndDate() && $event->getEventEndDate()->format('Y-m-d') != $event->getEventStartDate()->format('Y-m-d'))
        {
            $eventObject["dateString"] = $eventObject["dateString"] . " to " . $event->getEventEndDate()->format('Y-m-d');
        }

        // Adding the end time if it is different from the start time
        if ($event->getEventEndDate() && $event->getEventEndDate()->format('H:i') != $event->getEventStartDate()->format('H:i')) {
            $eventObject["timeString"] = $eventObject["timeString"] . ' to ' . $event->getEventEndDate()->format('H:i');
        }

        // Formating the event categories
        foreach ($event->getCategories() as $category)
        {
            $eventObject["categories"] .= $category->getCategoryName() . ", ";
        }

        $eventObject["categories"] = substr($eventObject["categories"], 0, -2);

        // The default subject line
        $subjectLine = $event->getUserId()->getUsername() . " posted \"" . $event->getEventTitle() . "\"";
        $type = "posted";

        // If the event has been modified
        if ($event->getCreationDate()->format('Y-m-d H:i:s') != $event->getModificationDate()->format('Y-m-d H:i:s'))
        {
            // The event has been EDITED!
            $subjectLine = $event->getUserId()->getUsername() . " updated \"" . $event->getEventTitle() . "\"";
            $type = "updated";
        }

        $email = (new TemplatedEmail())
            ->from(new Address('zueventsproject@gmail.com', 'ze Events Mailer'))
            ->to((string) $user->getEmail())
            ->subject($subjectLine)
            ->htmlTemplate('notification/follower_update_email.html.twig')
            ->context([
                'frontendUrl' => $_ENV['FRONTEND_URL'],
                'username' => $event->getUserId()->getUsername() ?? 'TEST',
                'event' => $eventObject,
                'status' => $type
            ]);


        try {
            // Sending the email
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // TODO
        }
    }

}
