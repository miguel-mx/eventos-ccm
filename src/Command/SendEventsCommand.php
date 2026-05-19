<?php

namespace App\Command;

use App\Entity\EventSeminar;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Repository\EventSeminarRepository;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;

#[AsCommand(
    name: 'app:send-events',
    description: 'Send an email with next or current week events',
)]
class SendEventsCommand extends Command
{
    private $eventRepository;
    private $mailer;
    private $twig;

    public function __construct(EventSeminarRepository $eventSeminarRepository, MailerInterface $mailer, Environment $twig)
    {
        parent::__construct();
        $this->eventRepository = $eventSeminarRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;

    }

    protected function configure(): void
    {
        $this
            ->setDescription('Sends an email with weekly events')
            ->addOption(
                'current',
                null,
                InputOption::VALUE_NONE,
                'If set, sends this week\'s events instead of next week\'s'
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isCurrentWeek = $input->getOption('current');

        $today = new \DateTimeImmutable();

        if ($isCurrentWeek) {
            $output->writeln('Sending email with this week\'s events...');
            // Set start of week - this monday
            $startOfWeek = $today->modify('monday this week')->setTime(0, 0, 0);
        } else {
            $output->writeln('Sending email with next week\'s events...');
            // Set start of week - next monday
            $startOfWeek = $today->modify('next monday')->setTime(0, 0, 0);
        }

        $endOfWeek = $startOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $events = $this->eventRepository->findEventsBetweenDates($startOfWeek, $endOfWeek);

        // Render email content
        $htmlContent = $this->twig->render('emails/send_events.html.twig', [
            'events' => $events,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ]);

        $textContent = $this->twig->render('emails/send_events.txt.twig', [
            'events' => $events,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ]);


        // Create email
        $email = (new Email())
            ->from('swmail@matmor.unam.mx')
            ->to('miguel@matmor.unam.mx')  // Replace or loop for multiple recipients
            ->subject('Upcoming Events: Week of ' . $startOfWeek->format('F j'))
            ->html($htmlContent)
            ->text($textContent);

        $this->mailer->send($email);

        $output->writeln($textContent);

        return Command::SUCCESS;
    }
}
