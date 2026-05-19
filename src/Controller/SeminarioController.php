<?php

namespace App\Controller;

use App\Entity\Seminario;
use App\Form\SeminarioType;
use App\Repository\SeminarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/seminario')]
final class SeminarioController extends AbstractController
{
    #[Route('/index/{token?}', name: 'app_seminario_index', methods: ['GET'])]
    public function index(SeminarioRepository $seminarioRepository, ?string $token = null): Response
    {
        return $this->render('seminario/index.html.twig', [
            'seminarios' => $seminarioRepository->findAll(),
            'env_token' => $_ENV['SEMINARIO_TOKEN'],
            'token' => $token,
        ]);
    }

    #[Route('/new', name: 'app_seminario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
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
    public function show(Seminario $seminario): Response
    {
        return $this->render('seminario/show.html.twig', [
            'seminario' => $seminario,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_seminario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seminario $seminario, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete'.$seminario->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($seminario);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_seminario_index', [], Response::HTTP_SEE_OTHER);
    }
}
