<?php

namespace App\Command;

use App\Entity\EventSeminar;
use App\Entity\Seminario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-json',
    description: 'Import events from a structured JSON export file into the database',
)]
class ImportJsonEventsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to the JSON file to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);
        if ($data === null) {
            $io->error('Invalid JSON.');
            return Command::FAILURE;
        }

        $eventType   = $data['event_type']  ?? 'UNKNOWN';
        $responsable = $data['responsable'] ?? '';
        $events      = $data['events']      ?? [];

        $seminario = $this->em->getRepository(Seminario::class)->findOneBy(['name' => $eventType]);
        if (!$seminario) {
            if (!$io->confirm("Seminario '$eventType' does not exist. Create it now?", false)) {
                $io->warning('Import cancelled.');
                return Command::FAILURE;
            }
            $seminario = new Seminario();
            $seminario->setName($eventType);
            $seminario->setLocation('Auditorio CCM');
            $seminario->setStart(\DateTime::createFromFormat('H:i', '12:00'));
            $this->em->persist($seminario);
            $this->em->flush(); // flush so slug is generated before events reference it
            $io->info("Created new Seminario: $eventType");
        } else {
            $io->info("Found existing Seminario: $eventType");
        }

        $imported = 0;
        $skipped  = 0;

        foreach ($events as $event) {
            $title   = $event['title']    ?? '';
            $dateIso = $event['date_iso'] ?? '';
            $time    = $event['time']     ?? '12:00';

            $startDt = \DateTime::createFromFormat('Y-m-d H:i', "$dateIso $time");
            if (!$startDt) {
                $io->warning("Skipping event with invalid date '$dateIso $time': $title");
                ++$skipped;
                continue;
            }

            // Idempotency: skip if already imported
            $existing = $this->em->getRepository(EventSeminar::class)
                ->findOneBy(['title' => $title, 'start' => $startDt]);
            if ($existing) {
                $io->note("Already imported, skipping: $title ($dateIso)");
                ++$skipped;
                continue;
            }

            $endDt = (clone $startDt)->modify('+1 hour');

            $notes = $event['notes'] ?? null;
            if (!empty($event['speaker_bio'])) {
                $bioBlock = "---\n" . $event['speaker_bio'];
                $notes    = $notes ? "$notes\n\n$bioBlock" : $bioBlock;
            }

            $entity = new EventSeminar();
            $entity->setSeminar($seminario);
            $entity->setTitle($title);
            $entity->setSpeaker($event['speaker'] ?? '');
            $entity->setInstitution($event['speaker_affiliation'] ?? '');
            $entity->setStart($startDt);
            $entity->setEnd($endDt);
            $entity->setLocation($event['location'] ?? '');
            $entity->setAbstract($event['abstract'] ?? null);
            $entity->setOrganizers($responsable);
            $entity->setNotes($notes);

            $this->em->persist($entity);
            ++$imported;
        }

        $this->em->flush();

        $io->success("Import complete: $imported imported, $skipped skipped.");
        return Command::SUCCESS;
    }
}
