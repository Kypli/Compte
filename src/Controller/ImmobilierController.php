<?php

namespace App\Controller;

use App\Entity\Immobilier;
use App\Form\ImmobilierType;
use App\Repository\ImmobilierRepository;
use App\Repository\MobilierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @Route("/immobilier", name="immobilier")
 */
#[IsGranted("ROLE_USER")]
#[Route("/immobilier", name: "immobilier")]
class ImmobilierController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    #[Route("/", name: "", methods: ["GET"])]
    public function index(ImmobilierRepository $immobilierRepository, MobilierRepository $mobilierRepository): Response
    {
        $user = $this->getUser();
        $immobiliers = $immobilierRepository->findByUser($user);
        $mobiliers = $mobilierRepository->findByUser($user);
        $totalImmobilier = $immobilierRepository->sumValueByUser($user);
        $totalMobilier = $mobilierRepository->sumValueByUser($user);

        return $this->render('immobilier/index.html.twig', [
            'immobiliers' => $immobiliers,
            'mobiliers' => $mobiliers,
            'total_immobilier' => $totalImmobilier,
            'total_mobilier' => $totalMobilier,
            'total_patrimoine' => $totalImmobilier + $totalMobilier,
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    #[Route("/new", name: "_new", methods: ["GET", "POST"])]
    public function new(Request $request, ImmobilierRepository $immobilierRepository): Response
    {
        $immobilier = (new Immobilier())->setUser($this->getUser());
        $form = $this->createForm(ImmobilierType::class, $immobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immobilierRepository->add($immobilier, true);

            return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('immobilier/new.html.twig', [
            'immobilier' => $immobilier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    #[Route("/{id}", name: "_show", methods: ["GET"])]
    public function show(Immobilier $immobilier): Response
    {
        $this->denyAccessUnlessAssetOwner($immobilier);

        return $this->render('immobilier/show.html.twig', [
            'immobilier' => $immobilier,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
     */
    #[Route("/{id}/edit", name: "_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Immobilier $immobilier, ImmobilierRepository $immobilierRepository): Response
    {
        $this->denyAccessUnlessAssetOwner($immobilier);

        $form = $this->createForm(ImmobilierType::class, $immobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $immobilierRepository->add($immobilier, true);

            return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('immobilier/edit.html.twig', [
            'immobilier' => $immobilier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="_delete", methods={"POST"})
     */
    #[Route("/{id}", name: "_delete", methods: ["POST"])]
    public function delete(Request $request, Immobilier $immobilier, ImmobilierRepository $immobilierRepository): Response
    {
        $this->denyAccessUnlessAssetOwner($immobilier);

        if ($this->isCsrfTokenValid('delete'.$immobilier->getId(), $request->request->get('_token'))) {
            $immobilierRepository->remove($immobilier, true);
        }

        return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessAssetOwner(Immobilier $immobilier): void
    {
        if ($immobilier->getUser()?->getId() !== $this->getUser()?->getId()) {
            throw $this->createAccessDeniedException('Ce bien immobilier ne vous appartient pas.');
        }
    }
}
