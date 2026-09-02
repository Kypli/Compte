<?php

namespace App\Controller;

use App\Entity\Mobilier;
use App\Form\MobilierType;
use App\Repository\MobilierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @Route("/mobilier", name="mobilier")
 */
#[IsGranted("ROLE_USER")]
#[Route("/mobilier", name: "mobilier")]
class MobilierController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    #[Route("/", name: "", methods: ["GET"])]
    public function index(MobilierRepository $mobilierRepository): Response
    {
        return $this->redirectToRoute('immobilier', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    #[Route("/new", name: "_new", methods: ["GET", "POST"])]
    public function new(Request $request, MobilierRepository $mobilierRepository): Response
    {
        $mobilier = (new Mobilier())->setUser($this->getUser());
        $form = $this->createForm(MobilierType::class, $mobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mobilierRepository->add($mobilier, true);

            return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mobilier/new.html.twig', [
            'mobilier' => $mobilier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    #[Route("/{id}", name: "_show", methods: ["GET"])]
    public function show(Mobilier $mobilier): Response
    {
        $this->denyAccessUnlessAssetOwner($mobilier);

        return $this->render('mobilier/show.html.twig', [
            'mobilier' => $mobilier,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
     */
    #[Route("/{id}/edit", name: "_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Mobilier $mobilier, MobilierRepository $mobilierRepository): Response
    {
        $this->denyAccessUnlessAssetOwner($mobilier);

        $form = $this->createForm(MobilierType::class, $mobilier);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mobilierRepository->add($mobilier, true);

            return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('mobilier/edit.html.twig', [
            'mobilier' => $mobilier,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="_delete", methods={"POST"})
     */
    #[Route("/{id}", name: "_delete", methods: ["POST"])]
    public function delete(Request $request, Mobilier $mobilier, MobilierRepository $mobilierRepository): Response
    {
        $this->denyAccessUnlessAssetOwner($mobilier);

        if ($this->isCsrfTokenValid('delete'.$mobilier->getId(), $request->request->get('_token'))) {
            $mobilierRepository->remove($mobilier, true);
        }

        return $this->redirectToRoute('immobilier', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessAssetOwner(Mobilier $mobilier): void
    {
        if ($mobilier->getUser()?->getId() !== $this->getUser()?->getId()) {
            throw $this->createAccessDeniedException('Ce bien mobilier ne vous appartient pas.');
        }
    }
}
