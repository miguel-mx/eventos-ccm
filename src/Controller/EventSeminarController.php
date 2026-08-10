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

final class EventSeminarController extends AbstractController
{
    #[Route('/todos-los-eventos', name: 'app_event_seminar_index', methods: ['GET'])]
    public function index(EventSeminarRepository $eventSeminarRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $q = trim($request->query->get('q', ''));

        $query = $q !== ''
            ? $eventSeminarRepository->searchQueryBuilder($q)
            : $eventSeminarRepository->findAllQueryBuilder();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('event_seminar/index.html.twig', [
            'event_seminars' => $pagination,
            'q' => $q,
        ]);
    }

    #[Route('/calendario', name: 'app_event_seminar_calendario', methods: ['GET'])]
    public function calendario(): Response
    {
        return $this->render('event_seminar/calendario.html.twig');
    }


    #[Route('/new/{id}', name: 'app_event_seminar_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Seminario $seminario): Response
    {
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para crear un nuevo evento.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        $eventSeminar = new EventSeminar();
        $eventSeminar->setSeminar($seminario);

        $organizers = $seminario->getOrganizers()->toArray();
        $eventSeminar->setOrganizers(implode(', ', array_map(fn($o) => $o->getName(), $organizers)));
        $eventSeminar->setLocation($seminario->getLocation());

        $dateStart = new \DateTime('now');
        $startTime = new \DateTime($seminario->getStart()->format('H:i'));

        $form = $this->createForm(EventSeminarType::class, $eventSeminar, [
            'default_time' => $startTime,
            'default_date' => $dateStart,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date = $form->get('start_date')->getData();
            $time = $form->get('start_time')->getData();
            $duration = $form->get('event_duration')->getData();

            $dateTimeStart = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
            $dateTimeEnd = (clone $dateTimeStart)->modify("+{$duration} hours");

            $eventSeminar->setStart($dateTimeStart);
            $eventSeminar->setEnd($dateTimeEnd);

            $entityManager->persist($eventSeminar);
            $entityManager->flush();

            $this->addFlash('success', 'El evento se registró exitosamente.');

            return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_seminar/new.html.twig', [
            'event_seminar' => $eventSeminar,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_event_seminar_show', methods: ['GET'], priority: -1)]
    public function show(EventSeminar $eventSeminar): Response
    {
        return $this->render('event_seminar/show.html.twig', [
            'event_seminar' => $eventSeminar,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_event_seminar_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EventSeminar $eventSeminar, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para editar un evento.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($eventSeminar->getStart() < new \DateTime()) {
            $this->addFlash('warning', 'No se puede modificar un evento pasado.');
            return $this->redirectToRoute('app_event_seminar_show', ['slug' => $eventSeminar->getSlug()]);
        }

        $startDate = new \DateTime($eventSeminar->getStart()->format('Y-m-d'));
        $startTime = new \DateTime($eventSeminar->getStart()->format('H:i:s'));

        $form = $this->createForm(EventSeminarType::class, $eventSeminar, [
            'default_date' => $startDate,
            'default_time' => $startTime,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $date = $form->get('start_date')->getData();
            $time = $form->get('start_time')->getData();
            $duration = $form->get('event_duration')->getData();

            $dateTimeStart = new \DateTime($date->format('Y-m-d') . ' ' . $time->format('H:i:s'));
            $dateTimeEnd = (clone $dateTimeStart)->modify("+{$duration} hours");

            $eventSeminar->setStart($dateTimeStart);
            $eventSeminar->setEnd($dateTimeEnd);

            $entityManager->flush();

            $this->addFlash('success', 'El evento se modificó correctamente.');

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
                    'url' => $this->generateUrl('app_event_seminar_show', ['slug' => $event->getSlug()]),
                ],
            ];
        }

        return new JsonResponse($responseArray);
    }

    #[Route('/{slug}/calendar', name: 'event_calendar_export')]
    public function exportEventToCalendar(EventSeminar $event): Response
    {
        $description = <<<TEXT
{$event->getTitle()}
{$event->getSpeaker()} - {$event->getInstitution()}

{$event->getAbstract()}
TEXT;

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
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para eliminar un evento.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$eventSeminar->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($eventSeminar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_seminar_index', [], Response::HTTP_SEE_OTHER);
    }

    private function isAuthorized(Request $request): bool
    {
        return $request->getSession()->get('ccm_authorized') === true;
    }
}
