<?php

namespace App\Controller;

use App\Entity\Compte;

use App\Form\CompteType;

use App\Repository\CompteRepository;
use App\Repository\OperationRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/compte", name="compte")
 */
class CompteController extends AbstractController
{
	/**
	 * @Route("/", name="")
	 */
	public function index(CompteRepository $cr): Response
	{
		return $this->render('compte/index.html.twig', [
			'comptes' => $cr->findAll(),
		]);
	}

	/**
	 * @Route("/new", name="_new", methods={"GET", "POST"})
	 */
	public function new(Request $request, CompteRepository $cr): Response
	{
		$compte = new Compte();
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$cr->add($compte, true);

			return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/new.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_show", methods={"GET"})
	 */
	public function show(Compte $compte, CompteRepository $cr): Response
	{
		$categories = $compte->getCategories();

		foreach(){

		}
		$operations = $cr->getOperationsByDateAndCompte($compte->getId(), date('Y'));
		dump($operations);
		die;
		return $this->render('compte/show.html.twig', [
			'compte' => $compte,
		]);
	}

	/**
	 * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
	 */
	public function edit(Request $request, Compte $compte, CompteRepository $cr): Response
	{
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$cr->add($compte, true);

			return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/edit.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_delete", methods={"POST"})
	 */
	public function delete(Request $request, Compte $compte, CompteRepository $cr): Response
	{
		if ($this->isCsrfTokenValid('delete'.$compte->getId(), $request->request->get('_token'))) {
			$cr->remove($compte, true);
		}

		return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
	}
}
