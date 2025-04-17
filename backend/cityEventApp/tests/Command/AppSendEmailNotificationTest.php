<?php

namespace App\Tests\Command;
use App\Command\AppSendNotificationsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class AppSendEmailNotificationTest extends KernelTestCase
{
    use MailerAssertionsTrait;
    private Application $application;

    protected function setUp(): void
    {
        self::bootKernel();

        // Create the Application instance with the name of the app
        $this->application = new Application('app'); // Here, we use 'app' as the application name

        // Add the command to the application
        $this->application->add(
            self::getContainer()->get(AppSendNotificationsCommand::class)
        );

    }


    public function testCommandRetrievesDataAndSendsEmails(): void
    {
        // Find and run the command
        $command = $this->application->find('app:send-notifications');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $emails = $this->getMailerMessages();
        $this->assertCount(1, $emails, 'Only one email should be sent');


        $email = $emails[0];


        // Assert recipient and subject
        $this->assertEquals('pUserS15@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Upcoming Events Reminder', $email->getSubject());

        // Assert email content contains expected text
        $textBody = $email->getTextBody();
        // Build dynamic strings
        $now = new \DateTimeImmutable('now');
        $today = $now->format('Y-m-d');
        $tomorrow = $now->modify('+1 day')->format('Y-m-d');
        $nextWeek = $now->modify('+7 days')->format('Y-m-d');

        $this->assertStringContainsString($today, $textBody);
        $this->assertStringContainsString($tomorrow, $textBody);
        $this->assertStringContainsString($nextWeek, $textBody);

        $this->assertStringContainsString('Event Today', $textBody);
        $this->assertStringContainsString('Event Tomorrow', $textBody);
        $this->assertStringContainsString('Event in 7 Days', $textBody);

    }
}