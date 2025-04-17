<?php

namespace App\Tests\Command;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\ScheduleBundle\Command\ScheduleRunCommand;
use App\Command\AppSendNotificationsCommand;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\UserRepository;
use App\Repository\BookmarkedEventRepository;
use Doctrine\ORM\EntityManagerInterface;
class SchedulerTest extends KernelTestCase
{
    private $mailer;
    private $userRepository;
    private $bookmarkRepository;
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookmarkRepository = $this->createMock(BookmarkedEventRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testSchedulerRunsNotificationCommand(): void
    {
        // Register Commands in Application
        $application = new Application();
        $application->add(new AppSendNotificationsCommand(
            $this->userRepository,
            $this->bookmarkRepository,
            $this->mailer,
            $this->entityManager
        ));
        $application->add(self::getContainer()->get(ScheduleRunCommand::class));

        // Execute `schedule:run`
        $commandTester = new CommandTester($application->find('schedule:run'));
        $commandTester->execute([]);

        // Assert that `app:send-notifications` was executed
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running CommandTask', $output);
        $this->assertStringContainsString('Test sending email notifications based on user preferences', $output);
    }
}