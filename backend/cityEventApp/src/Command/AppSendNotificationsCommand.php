<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\BookmarkedEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:send-notifications',
    description: 'Sends email notifications based on user preferences.',
)]
class AppSendNotificationsCommand extends Command
{
    private UserRepository $userRepository;
    private BookmarkedEventRepository $bookmarkRepository;
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        BookmarkedEventRepository $bookmarkRepository,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->bookmarkRepository = $bookmarkRepository;
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findUsersWithEmailNotification();
        if (empty($users)) {$output->writeln("No users for sending email today");}
        foreach ($users as $user) {
            $events = $this->bookmarkRepository->findBookmarkedEventsForUserByNotificationTime($user);
            if (!empty($events)) {
                $eventList = "";
                foreach ($events as $event) {
                    $eventList .= "- " . $event->getEventTitle() . " (Starts: " . $event->getEventStartDate()->format('Y-m-d H:i') . ")\n";
                    $eventList .= "    click here for details: " . $event->getEventLink() . "\n";
                }

                // Send email
                $email = (new Email())
                    ->from('noreply@cityEventApp.com')
                    ->to($user->getEmail())
                    ->subject('Upcoming Events Reminder')
                    ->text("Hello {$user->getUsername()},\n\nYou have upcoming events scheduled:\n\n$eventList\n\nBest Regards,\nYourApp Team");

                $this->mailer->send($email);
                $output->writeln("Email sent to: " . $user->getEmail());

            }
        }
        return Command::SUCCESS;
    }
}
