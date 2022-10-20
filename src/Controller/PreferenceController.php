<?php

namespace App\Controller;

use App\Entity\UserPreference;
use App\Form\UserPreferenceType;
use App\Repository\UserPreferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/preference", name="preference")
 */
class PreferenceController extends AbstractController
{
    /**
     * @Route("/", name="", methods={"GET"})
     */
    public function index(UserPreferenceRepository $userPreferenceRepository): Response
    {
        return $this->render('preference/index.html.twig', [
            'user_preferences' => $userPreferenceRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_preference_new", methods={"GET", "POST"})
     */
    public function new(Request $request, UserPreferenceRepository $userPreferenceRepository): Response
    {
        $userPreference = new UserPreference();
        $form = $this->createForm(UserPreferenceType::class, $userPreference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userPreferenceRepository->add($userPreference, true);

            return $this->redirectToRoute('app_preference_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('preference/new.html.twig', [
            'user_preference' => $userPreference,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_preference_show", methods={"GET"})
     */
    public function show(UserPreference $userPreference): Response
    {
        return $this->render('preference/show.html.twig', [
            'user_preference' => $userPreference,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_preference_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, UserPreference $userPreference, UserPreferenceRepository $userPreferenceRepository): Response
    {
        $form = $this->createForm(UserPreferenceType::class, $userPreference);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userPreferenceRepository->add($userPreference, true);

            return $this->redirectToRoute('app_preference_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('preference/edit.html.twig', [
            'user_preference' => $userPreference,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_preference_delete", methods={"POST"})
     */
    public function delete(Request $request, UserPreference $userPreference, UserPreferenceRepository $userPreferenceRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$userPreference->getId(), $request->request->get('_token'))) {
            $userPreferenceRepository->remove($userPreference, true);
        }

        return $this->redirectToRoute('app_preference_index', [], Response::HTTP_SEE_OTHER);
    }
}
