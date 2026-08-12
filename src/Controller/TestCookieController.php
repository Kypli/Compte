<?php

namespace App\Controller;

use App\Service\CookieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TestCookieController extends AbstractController
{
	#[Route('/test/cookies/delete-all', name: 'test_cookie_delete_all', methods: ['POST'])]
	public function deleteAll(
		Request $request,
		CookieService $cookieService,
		KernelInterface $kernel,
		TokenStorageInterface $tokenStorage
	): Response
	{
		if ('prod' === $kernel->getEnvironment()){
			throw $this->createNotFoundException();
		}

		if (!$this->isCsrfTokenValid('delete_all_test_cookies', $request->request->get('_token'))){
			$this->addFlash('error', 'Impossible de supprimer les cookies.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		$tokenStorage->setToken(null);

		if ($request->hasSession()){
			$request->getSession()->invalidate();
		}

		$response = $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);

		return $cookieService->removeAllCookies($response, $request);
	}
}
