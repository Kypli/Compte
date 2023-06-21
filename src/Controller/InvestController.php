<?php

namespace App\Controller;

use App\Entity\Invest;
use App\Form\InvestType;
use App\Repository\InvestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/investissement", name="investissement"))
 */
class InvestController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index(InvestRepository $investRepository): Response
    {
        return $this->render('invest/index.html.twig', [
            'invests' => $investRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="_new", methods={"GET", "POST"})
     */
    public function new(Request $request, InvestRepository $investRepository): Response
    {
        $invest = new Invest();
        $form = $this->createForm(InvestType::class, $invest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $investRepository->add($invest, true);

            return $this->redirectToRoute('app_invest_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('invest/new.html.twig', [
            'invest' => $invest,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_show", methods={"GET"})
     */
    public function show(Invest $invest): Response
    {
        return $this->render('invest/show.html.twig', [
            'invest' => $invest,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Invest $invest, InvestRepository $investRepository): Response
    {
        $form = $this->createForm(InvestType::class, $invest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $investRepository->add($invest, true);

            return $this->redirectToRoute('app_invest_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('invest/edit.html.twig', [
            'invest' => $invest,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="_delete", methods={"POST"})
     */
    public function delete(Request $request, Invest $invest, InvestRepository $investRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$invest->getId(), $request->request->get('_token'))) {
            $investRepository->remove($invest, true);
        }

        return $this->redirectToRoute('app_invest_index', [], Response::HTTP_SEE_OTHER);
    }
}
