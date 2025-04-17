<?php

namespace App\Command;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:load-test-fixtures')]
class LoadTestFixturesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Load fixtures from tests/tests/DataFixtures for testing.')
            ->addArgument('fixture', InputArgument::OPTIONAL, 'The name of the fixture to load (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new Loader();

        // Correct file path
        $path = __DIR__ . '/../../tests/tests/DataFixtures';
        $fixtures = glob($path . '/*.php');

        $fixtureToLoad = $input->getArgument('fixture');

        // If a fixture name is provided, filter by that fixture name
        if ($fixtureToLoad) {
            $fixtureFile = $path . '/' . $fixtureToLoad . '.php';

            if (file_exists($fixtureFile)) {
                require_once $fixtureFile;

                // Adjust class name with the correct namespace
                $className = 'App\\Tests\\tests\\DataFixtures\\' . $fixtureToLoad;

                if (class_exists($className)) {
                    $loader->addFixture(new $className());
                    $output->writeln("<info>Loaded fixture: {$className}</info>");
                } else {
                    $output->writeln("<error>Fixture class not found: {$className}</error>");
                }
            } else {
                $output->writeln("<error>Fixture file not found: {$fixtureFile}</error>");
            }
        } else {
            // Load all fixtures if no argument is passed
            foreach ($fixtures as $fixtureFile) {
                require_once $fixtureFile;

                // Adjust class name with the correct namespace
                $className = 'App\\Tests\\tests\\DataFixtures\\' . basename($fixtureFile, '.php');

                if (class_exists($className)) {
                    $loader->addFixture(new $className());
                    $output->writeln("<info>Loaded fixture: {$className}</info>");
                } else {
                    $output->writeln("<error>Fixture class not found: {$className}</error>");
                }
            }
        }

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);

        $executor->purge();
        $executor->execute($loader->getFixtures());

        $output->writeln('<info>Test fixtures loaded successfully.</info>');

        return Command::SUCCESS;
    }
}
