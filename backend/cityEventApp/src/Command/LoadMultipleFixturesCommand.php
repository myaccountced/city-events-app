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

#[AsCommand(name: 'app:load-multiple-test-fixtures')]
class LoadMultipleFixturesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }
    protected static $defaultName = 'app:load-multiple-test-fixtures';

    protected function configure()
    {
        $this
            ->setDescription('Load multiple data fixtures')
            ->addArgument('fixture', InputArgument::IS_ARRAY, 'List of fixture classes to load');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loader = new Loader();

        // Correct file path
        $path = __DIR__ . '/../../tests/tests/DataFixtures';
        $fixtures = $input->getArgument('fixture');

        if (empty($fixtures)) {
            // Load all fixtures if no argument is passed
            $fixtureFiles = glob($path . '/*.php');

            foreach ($fixtureFiles as $fixtureFile) {
                $this->loadFixture($loader, $fixtureFile, $output);
            }
        } else {
            // Load specific fixtures passed as arguments
            foreach ($fixtures as $fixtureName) {
                $fixtureFile = $path . '/' . $fixtureName . '.php';
                $this->loadFixture($loader, $fixtureFile, $output);
            }
        }

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());

        $output->writeln('<info>Fixtures loaded successfully.</info>');

        return Command::SUCCESS;
    }

    private function loadFixture(Loader $loader, string $fixtureFile, OutputInterface $output): void
    {
        if (!file_exists($fixtureFile)) {
            $output->writeln("<error>Fixture file not found: {$fixtureFile}</error>");
            return;
        }

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