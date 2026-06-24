<?php

namespace App\Controller;

use App\Entity\EventSeminar;
use App\Entity\Seminario;
use App\Form\EventSeminarType;
use App\Repository\EventSeminarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/eventos')]
final class EventSeminarController extends AbstractController
{
    #[Route(name: 'app_event_seminar_index', methods: ['GET'])]
    public function index(EventSeminarRepository $eventSeminarRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $eventSeminarRepository->findAllQueryBuilder(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('event_seminar/index.html.twig', [
            'event_seminars' => $pagination,
        ]);
    }

    #[Route('/calendario', name: 'app_event_seminar_calendario', methods: ['GET'])]
    public function calendario(): Response
    {
        return $this->render('event_seminar/calendario.html.twig');
    }

    #[Route('/new/{id}/{token}', name: 'app_event_seminar_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Seminario $seminario, ?string $token = null): Response
    {
        if ($token !== $_ENV['SEMINARIO_TOKEN']) {

            $this->addFlash(
                'danger',
                'Debe tener credenciales para crear un nuevo evento!'
            );

            return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
        }

        $eventSeminar = new EventSeminar();

        // Initial data from Seminario
        $eventSeminar->setSeminar($seminario);
        $organizers = $seminario->getOrganizers()->toArray(); // Convert Doctrine Collection to array

        // Get Seminar Start Date & Time
        $eventSeminar->setOrganizers(implode(', ', array_map(fn($o) => $o->getName(), $organizers)));
        //$eventSeminar->setStart($seminario->getStart());
        $dateStart = new \DateTime('now');
        $startTime = new \DateTime($seminario->getStart()->format('H:i'));

        //Set location
        $eventSeminar->setLocation($seminario->getLocation());

        $form = $this->createForm(EventSeminarType::class, $eventSeminar, [
            'default_time' => $startTime,
            'default_date' => $dateStart,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Get the date and time from the form
            $date = $form->get('start_date')->getData();
            $time = $form->get('start_time')->getData();
            $duration = $form->get('event_duration')->getData();

            // Merge them into a single DateTime object
            $dateTimeStart = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
            $dateTimeEnd = (clone $dateTimeStart)->modify("+{$duration} hours");

            // Set start and end of Event in \Datetime format
            $eventSeminar->setStart($dateTimeStart);
            $eventSeminar->setEnd($dateTimeEnd);

            $entityManager->persist($eventSeminar);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'El evento se registró exitosamente!'
            );

            return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_seminar/new.html.twig', [
            'event_seminar' => $eventSeminar,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_event_seminar_show', methods: ['GET'])]
    public function show(EventSeminar $eventSeminar): Response
    {
        return $this->render('event_seminar/show.html.twig', [
            'event_seminar' => $eventSeminar,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_event_seminar_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EventSeminar $eventSeminar, EntityManagerInterface $entityManager): Response
    {
        // Check if the event's start date is in the past
        if ($eventSeminar->getStart() < new \DateTime()) {

            $this->addFlash(
                'warning',
                'No se puede modificar un evento pasado!'
            );

            return $this->redirectToRoute('app_event_seminar_show', ['slug' => $eventSeminar->getSlug()]);
        }

        $startDateStr = $eventSeminar->getStart()->format('Y-m-d');
        $startTimeStr = $eventSeminar->getStart()->format('H:i:s');
        $startDate = new \DateTime($startDateStr);
        $startTime = new \DateTime($startTimeStr);

        $form = $this->createForm(EventSeminarType::class, $eventSeminar, [
            'default_date' => $startDate,
            'default_time' => $startTime,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Get the date and time from the form
            $date = $form->get('start_date')->getData();
            $time = $form->get('start_time')->getData();
            $duration = $form->get('event_duration')->getData();

            // Merge them into a single DateTime object
            $dateTimeStart = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
            $dateTimeEnd = (clone $dateTimeStart)->modify("+{$duration} hours");

            // Set start and end of Event in \Datetime format
            $eventSeminar->setStart($dateTimeStart);
            $eventSeminar->setEnd($dateTimeEnd);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'El evento se modificó correctamente!'
            );

            return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_seminar/edit.html.twig', [
            'event_seminar' => $eventSeminar,
            'form' => $form,
        ]);
    }

   #[Route('/consulta/json', name: 'event_seminar_json', methods: ['GET'])]
    public function getEvents(EventSeminarRepository $eventSeminarRepository): JsonResponse
    {
        $events = $eventSeminarRepository->findAll();

        $responseArray = [];
        foreach ($events as $event) {
            $responseArray[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'start' => $event->getStart()->format('Y-m-d\TH:i:s'),
                'end' => $event->getEnd()->format('Y-m-d\TH:i:s'),
                'extendedProps' => [
                    'description' => $event->getSeminar()->getName(),
                    'speaker' => $event->getSpeaker(),
                    'url' => '/eventos/' . $event->getSlug(),
                ],
            ];
        }
        return new JsonResponse($responseArray);
    }

    #[Route('/{slug}/calendar', name: 'event_calendar_export')]
    public function exportEventToCalendar(EventSeminar $event): Response
    {
        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }

        $description = <<<TEXT
{$event->getTitle()}
{$event->getSpeaker()} - {$event->getInstitution()}

{$event->getAbstract()}
TEXT;

// Escape line breaks and special characters for ICS
        $description = preg_replace("/(\r\n|\r|\n)/", "\\n", $description);
        $description = addcslashes($description, ",;");

        $icsContent = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//CCM-Seminars//EventCalendar//EN
BEGIN:VEVENT
UID:event-{$event->getSlug()}@matmor.unam.mx
DTSTAMP:{$event->getCreatedAt()->format('Ymd\THis')}
DTSTART:{$event->getStart()->format('Ymd\THis')}
DTEND:{$event->getEnd()->format('Ymd\THis')}
SUMMARY:{$event->getSeminar()->getName()}
DESCRIPTION:{$description}
LOCATION:{$event->getLocation()}
END:VEVENT
END:VCALENDAR
ICS;

        return new Response($icsContent, 200, [
            'Content-Type' => 'text/calendar',
            'Content-Disposition' => 'attachment; filename="event.ics"',
        ]);
    }

    #[Route('/{id}', name: 'app_event_seminar_delete', methods: ['POST'])]
    public function delete(Request $request, EventSeminar $eventSeminar, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$eventSeminar->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($eventSeminar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
    }
}
