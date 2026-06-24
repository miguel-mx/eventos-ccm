<?php

namespace App\Controller;

use App\Repository\EventSeminarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EventSeminarRepository $eventSeminarRepository): Response
    {
        $today = new \DateTimeImmutable();
        $startOfWeek = $today->modify(('Monday' === $today->format('l')) ? 'this monday' : 'last monday')->setTime(0, 0, 0);
        $endOfWeek = $startOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $events = $eventSeminarRepository->findEventsBetweenDates($startOfWeek, $endOfWeek);

        return $this->render('home/index.html.twig', [
            'event_seminars' => $events,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ]);
    }
}
