<?php

namespace App\Command;

use App\Repository\EventSeminarRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:send-events',
    description: 'Sends a weekly email with seminar events',
)]
class SendEventsCommand extends Command
{
    public function __construct(
        private readonly EventSeminarRepository $eventRepository,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $mailerSender,
        private readonly string $mailerRecipient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'current',
            null,
            InputOption::VALUE_NONE,
            'Send this week\'s events instead of next week\'s'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isCurrentWeek = $input->getOption('current');

        $today = new \DateTimeImmutable();

        if ($isCurrentWeek) {
            $startOfWeek = $today->modify('monday this week')->setTime(0, 0, 0);
            $io->title('Enviando eventos de esta semana');
        } else {
            $startOfWeek = $today->modify('next monday')->setTime(0, 0, 0);
            $io->title('Enviando eventos de la próxima semana');
        }

        $endOfWeek = $startOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $events = $this->eventRepository->findEventsBetweenDates($startOfWeek, $endOfWeek);

        if (empty($events)) {
            $io->warning('No hay eventos programados para ese periodo. No se enviará el correo.');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Se encontraron %d evento(s).', count($events)));

        $htmlContent = $this->twig->render('emails/send_events.html.twig', [
            'events'      => $events,
            'startOfWeek' => $startOfWeek,
            'endOfWeek'   => $endOfWeek,
        ]);

        $textContent = $this->twig->render('emails/send_events.txt.twig', [
            'events'      => $events,
            'startOfWeek' => $startOfWeek,
            'endOfWeek'   => $endOfWeek,
        ]);

        $months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $monthName = $months[(int)$endOfWeek->format('n') - 1];
        $subject = sprintf(
            'Seminarios CCM · Semana del %s al %s de %s de %s',
            $startOfWeek->format('j'),
            $endOfWeek->format('j'),
            $monthName,
            $endOfWeek->format('Y')
        );

        $email = (new Email())
            ->from($this->mailerSender)
            ->to($this->mailerRecipient)
            ->subject($subject)
            ->html($htmlContent)
            ->text($textContent);

        $this->mailer->send($email);

        $io->success(sprintf('Correo enviado: "%s"', $subject));

        return Command::SUCCESS;
    }
}
