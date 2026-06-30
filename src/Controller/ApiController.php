<?php

namespace App\Controller;

use App\Repository\EventSeminarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/events/week', name: 'api_events_week', methods: ['GET'])]
    public function thisWeek(EventSeminarRepository $eventSeminarRepository): JsonResponse
    {
        $today = new \DateTimeImmutable();
        $startOfWeek = $today
            ->modify(('Monday' === $today->format('l')) ? 'this monday' : 'last monday')
            ->setTime(0, 0, 0);
        $endOfWeek = $startOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $events = $eventSeminarRepository->findEventsBetweenDates($startOfWeek, $endOfWeek);

        $data = array_map(fn($event) => [
            'id'          => $event->getId(),
            'slug'        => $event->getSlug(),
            'title'       => $event->getTitle(),
            'speaker'     => $event->getSpeaker(),
            'institution' => $event->getInstitution(),
            'abstract'    => $event->getAbstract(),
            'seminar'     => $event->getSeminar()->getName(),
            'location'    => $event->getLocation(),
            'start'       => $event->getStart()->format(\DateTimeInterface::ATOM),
            'end'         => $event->getEnd()->format(\DateTimeInterface::ATOM),
            'url'         => $event->getUrl(),
        ], $events);

        return $this->json([
            'week' => [
                'start' => $startOfWeek->format('Y-m-d'),
                'end'   => $endOfWeek->format('Y-m-d'),
            ],
            'count'  => count($data),
            'events' => $data,
        ]);
    }
}
