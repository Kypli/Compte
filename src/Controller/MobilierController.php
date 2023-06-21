<?php

namespace App\Controller;

use App\Entity\Mobilier;
use App\Form\MobilierType;
use App\Repository\MobilierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/mobilier", name="mobilier")
 */
class MobilierController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index(MobilierRepository $mobilierRepository): Response
    {
        return $this->render('mobilier/index.html.twig', [
            'mobiliers' => $mobilierRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    public function new(Request $request, MobilierRepository $mobilierRepository): Response
    {
        $mobilier = new Mobilier();
        $form = $this->createForm(MobilierType::class, $mobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mobilierRepository->add($mobilier, true);

            return $this->redirectToRoute('app_mobilier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('mobilier/new.html.twig', [
            'mobilier' => $mobilier,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    public function show(Mobilier $mobilier): Response
    {
        return $this->render('mobilier/show.html.twig', [
            'mobilier' => $mobilier,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Mobilier $mobilier, MobilierRepository $mobilierRepository): Response
    {
        $form = $this->createForm(MobilierType::class, $mobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mobilierRepository->add($mobilier, true);

            return $this->redirectToRoute('app_mobilier_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('mobilier/edit.html.twig', [
            'mobilier' => $mobilier,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_delete", methods={"POST"})
     */
    public function delete(Request $request, Mobilier $mobilier, MobilierRepository $mobilierRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$mobilier->getId(), $request->request->get('_token'))) {
            $mobilierRepository->remove($mobilier, true);
        }

        return $this->redirectToRoute('app_mobilier_index', [], Response::HTTP_SEE_OTHER);
    }
}
