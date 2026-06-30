<?php

namespace App\Controller;

use App\Entity\Seminario;
use App\Form\SeminarioType;
use App\Repository\EventSeminarRepository;
use App\Repository\SeminarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/seminario')]
final class SeminarioController extends AbstractController
{
    #[Route('/index/{token?}', name: 'app_seminario_index', methods: ['GET'])]
    public function index(SeminarioRepository $seminarioRepository, Request $request, ?string $token = null): Response
    {
        $isAuthorized = false;

        if ($token !== null && $token === $_ENV['SEMINARIO_TOKEN']) {
            $request->getSession()->set('ccm_authorized', true);
            $isAuthorized = true;
        } elseif ($request->getSession()->get('ccm_authorized') === true) {
            $isAuthorized = true;
        }

        return $this->render('seminario/index.html.twig', [
            'seminarios' => $seminarioRepository->findAll(),
            'is_authorized' => $isAuthorized,
        ]);
    }

    #[Route('/new', name: 'app_seminario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para realizar esta acción.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        $seminario = new Seminario();
        $form = $this->createForm(SeminarioType::class, $seminario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($seminario);
            $entityManager->flush();

            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seminario/new.html.twig', [
            'seminario' => $seminario,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_seminario_show', methods: ['GET'])]
    public function show(
        Seminario $seminario,
        Request $request,
        EventSeminarRepository $eventSeminarRepository,
        SeminarioRepository $seminarioRepository,
        PaginatorInterface $paginator
    ): Response {
        $year = $request->query->get('year') !== null
            ? (int) $request->query->get('year')
            : null;

        $years = $eventSeminarRepository->findYearsBySeminario($seminario);

        $pagination = $paginator->paginate(
            $eventSeminarRepository->findBySeminarioQueryBuilder($seminario, $year),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('seminario/show.html.twig', [
            'seminario' => $seminario,
            'event_seminars' => $pagination,
            'years' => $years,
            'selected_year' => $year,
            'other_seminarios' => $seminarioRepository->findOtherSeminarios($seminario),
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_seminario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seminario $seminario, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para realizar esta acción.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(SeminarioType::class, $seminario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('seminario/edit.html.twig', [
            'seminario' => $seminario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_seminario_delete', methods: ['POST'])]
    public function delete(Request $request, Seminario $seminario, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isAuthorized($request)) {
            $this->addFlash('danger', 'Debe tener credenciales para realizar esta acción.');
            return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete'.$seminario->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($seminario);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
    }

    private function isAuthorized(Request $request): bool
    {
        return $request->getSession()->get('ccm_authorized') === true;
    }
}
