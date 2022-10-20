<?php

namespace App\Controller;

use App\Entity\Immobilier;
use App\Form\ImmobilierType;
use App\Repository\ImmobilierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/immobilier", name="immobilier")
 */
class ImmobilierController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index(ImmobilierRepository $immobilierRepository): Response
    {
        return $this->render('immobilier/index.html.twig', [
            'immobiliers' => $immobilierRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    public function new(Request $request, ImmobilierRepository $immobilierRepository): Response
    {
        $immobilier = new Immobilier();
        $form = $this->createForm(ImmobilierType::class, $immobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immobilierRepository->add($immobilier, true);

            return $this->redirectToRoute('app_immobilier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('immobilier/new.html.twig', [
            'immobilier' => $immobilier,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    public function show(Immobilier $immobilier): Response
    {
        return $this->render('immobilier/show.html.twig', [
            'immobilier' => $immobilier,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Immobilier $immobilier, ImmobilierRepository $immobilierRepository): Response
    {
        $form = $this->createForm(ImmobilierType::class, $immobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immobilierRepository->add($immobilier, true);

            return $this->redirectToRoute('app_immobilier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('immobilier/edit.html.twig', [
            'immobilier' => $immobilier,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_delete", methods={"POST"})
     */
    public function delete(Request $request, Immobilier $immobilier, ImmobilierRepository $immobilierRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$immobilier->getId(), $request->request->get('_token'))) {
            $immobilierRepository->remove($immobilier, true);
        }

        return $this->redirectToRoute('app_immobilier_index', [], Response::HTTP_SEE_OTHER);
    }
}
