<?php

namespace App\Command;

use App\Entity\Seminario;
use App\Entity\EventSeminar;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-database',
    description: 'Add a short description for your command',
)]
class AppImportDatabaseCommand extends Command
{

    private Connection $connection;
    private EntityManagerInterface $entityManager;

    public function __construct(Connection $connection, EntityManagerInterface $entityManager   )
    {
        parent::__construct();
        $this->connection = $connection;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
  /*      $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');*/


     /*   $sql = 'SELECT * FROM seminario_legacy';
        $stmt = $this->connection->executeQuery($sql);
        $seminarios = $stmt->fetchAllAssociative(); // Fetch as an array

        // Output each seminar
        foreach ($seminarios as $sem) {

            $output->writeln('Time: ' . $sem['hora']);

            $time = $sem['hora'] ?? '00:00'; // Default to midnight if null
            //$dateTime = new  \DateTime('1970-01-01 ' . $time);
            $dateTime = DateTimeImmutable::createFromFormat("Y-m-d H:i", "1970-01-01" . " ". $time);

            $output->writeln('Seminar Name: ' . $sem['nombre'] . " - " . $dateTime->format('Y-m-d H:i'));

            if ($dateTime === false) {
                $output->writeln('Error al crear la fecha ' . " - " . $dateTime->format('Y-m-d H:i'));
                throw new \Exception("Invalid date format for time: " . $time);
            }

            $seminario = new Seminario();
            $seminario->setName($sem['nombre']);
            $seminario->setLocation($sem['lugar'] ?? '');

            // Convert to DateTime object
            $seminario->setStart($dateTime);

            //$seminario->setStart(new \DateTime($sem['hora'] ?? '00:00:00'));
            $this->entityManager->persist($seminario);
            $this->entityManager->flush();

        }*/

        $sql = 'SELECT * FROM evento_legacy';
        $stmt = $this->connection->executeQuery($sql);
        $eventos = $stmt->fetchAllAssociative(); // Fetch as an array

        /*foreach ($eventos as $evento) {

            $time = $evento['hora'] ?? '00:00'; // Default to midnight if null
            $date = $evento['fecha'];
            //$dateTime = new  \DateTime('1970-01-01 ' . $time);
            $dateTime = DateTimeImmutable::createFromFormat("Y-m-d H:i", $date . " ". $time);

            $output->writeln('Evento: ' . $evento['platica'] . " - " . $dateTime->format('Y-m-d H:i') . " Seminario ". $evento["sem_id"]);

            if ($dateTime === false) {
                $output->writeln('Error al crear la fecha ' . " - " . $dateTime->format('Y-m-d H:i'));
                throw new \Exception("Invalid date format for time: " . $time);
            }

            $eventSeminar = new EventSeminar();

            $eventSeminar->setLocation($evento['lugar'] ?? '');

            // Convert to DateTime object
            $eventSeminar->setStart($dateTime);
            $eventSeminar->setEnd($dateTime->modify('+1 hour'));

            $eventSeminar->setSpeaker($evento['ponente']);
            $eventSeminar->setInstitution($evento['origen']);
            $eventSeminar->setTitle($evento['platica']);
            $eventSeminar->setAbstract($evento['resumen']);
            $eventSeminar->setOrganizers($evento['responsable']);

            $seminarioId = $evento['sem_id'];

            // Retrieve the Seminar entity using its repository
            $seminario = $this->entityManager->getRepository(Seminario::class)->find($seminarioId);
            $eventSeminar->setSeminar($seminario);

            $this->entityManager->persist($eventSeminar);
        }

        $this->entityManager->flush();*/

        //while ($row = $stmt->fetchAssociative()) {

          //  $io->info(sprintf('Seminario %s - %s', $row['id'], $row['nombre']));

/*            $seminario = new Seminario();
            $seminario->setName($row['nombre']);
            $seminario->setLocation($row['lugar'] ?? '');
            $seminario->setStart(new \DateTime($row['hora'] ?? '00:00:00'));
            $this->entityManager->persist($seminario);*/
        //}

        /*// Import EventSeminar data
        $output->writeln('Importing EventSeminar data...');
        $sql = 'SELECT * FROM evento_legacy';
        $stmt = $connection->executeQuery($sql);
        while ($row = $stmt->fetchAssociative()) {
            $seminario = $this->entityManager->getRepository(Seminario::class)->find($row['sem_id']);

            $eventSeminar = new EventSeminar();
            $eventSeminar->setSeminar($seminario);
            $eventSeminar->setLocation($row['lugar']);
            $eventSeminar->setStart(new \DateTime($row['fecha'] . ' ' . $row['hora']));
            $eventSeminar->setSpeaker($row['ponente']);
            $eventSeminar->setInstitution($row['origen']);
            $eventSeminar->setTitle($row['platica']);
            $eventSeminar->setAbstract($row['resumen']);
            $this->entityManager->persist($eventSeminar);
        }*/

       // $this->entityManager->flush();
        $output->writeln('Data migration completed successfully.');

        return Command::SUCCESS;
    }
}
