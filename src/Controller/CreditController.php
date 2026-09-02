<?php

namespace App\Controller;

use App\Entity\Credit;
use App\Form\CreditType;
use App\Repository\CreditRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
#[Route("/credit", name: "credit")]
class CreditController extends AbstractController
{
    #[Route("/", name: "", methods: ["GET"])]
    public function index(CreditRepository $creditRepository): Response
    {
        $credits = $creditRepository->findByUser($this->getUser());
        $summary = [
            'activeCount' => 0,
            'initial' => 0.0,
            'remaining' => 0.0,
            'repaid' => 0.0,
            'monthly' => 0.0,
        ];

        foreach ($credits as $credit) {
            $summary['initial'] += $credit->getMontantInitial();
            $summary['remaining'] += $credit->getCapitalRestant();
            $summary['repaid'] += $credit->getMontantRembourse();
            if ($credit->isActif()) {
                ++$summary['activeCount'];
                $summary['monthly'] += $credit->getCoutMensuel();
            }
        }

        return $this->render('credit/index.html.twig', [
            'credits' => $credits,
            'summary' => $summary,
        ]);
    }

    #[Route("/new", name: "_new", methods: ["GET", "POST"])]
    public function new(Request $request, CreditRepository $creditRepository): Response
    {
        $credit = (new Credit())->setUser($this->getUser());
        $form = $this->createForm(CreditType::class, $credit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $creditRepository->add($credit, true);

            return $this->redirectToRoute('credit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('credit/new.html.twig', [
            'credit' => $credit,
            'form' => $form->createView(),
        ]);
    }

    #[Route("/{id}", name: "_show", methods: ["GET"])]
    public function show(Credit $credit): Response
    {
        $this->denyAccessUnlessCreditOwner($credit);

        return $this->render('credit/show.html.twig', [
            'credit' => $credit,
        ]);
    }

    #[Route("/{id}/edit", name: "_edit", methods: ["GET", "POST"])]
    public function edit(Request $request, Credit $credit, CreditRepository $creditRepository): Response
    {
        $this->denyAccessUnlessCreditOwner($credit);
        $form = $this->createForm(CreditType::class, $credit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $creditRepository->add($credit, true);

            return $this->redirectToRoute('credit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('credit/edit.html.twig', [
            'credit' => $credit,
            'form' => $form->createView(),
        ]);
    }

    #[Route("/{id}", name: "_delete", methods: ["POST"])]
    public function delete(Request $request, Credit $credit, CreditRepository $creditRepository): Response
    {
        $this->denyAccessUnlessCreditOwner($credit);

        if ($this->isCsrfTokenValid('delete'.$credit->getId(), (string) $request->request->get('_token'))) {
            $creditRepository->remove($credit, true);
        }

        return $this->redirectToRoute('credit', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessCreditOwner(Credit $credit): void
    {
        if ($credit->getUser()?->getId() !== $this->getUser()?->getId()) {
            throw $this->createAccessDeniedException('Ce crédit ne vous appartient pas.');
        }
    }
}
