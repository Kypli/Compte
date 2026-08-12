<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Entity\UserProfil;
use App\Repository\UserPreferenceRepository;
use App\Repository\UserProfilRepository;
use App\Repository\UserRepository;
use App\Service\CookieService;
use App\Service\GoogleOAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class GoogleAuthController extends AbstractController
{
    private const SESSION_STATE_KEY = 'google_oauth_state';

    #[Route('/connect/google', name: 'connect_google_start', methods: ['GET'])]
    public function start(Request $request, GoogleOAuthService $googleOAuth): Response
    {
        if (!$googleOAuth->isConfigured()){
            $this->addFlash('login_error', 'La connexion Google n\'est pas encore configurée.');

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        $state = bin2hex(random_bytes(32));
        $request->getSession()->set(self::SESSION_STATE_KEY, $state);

        return $this->redirect($googleOAuth->getAuthorizationUrl($this->getCallbackUrl(), $state));
    }

    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function check(
        Request $request,
        GoogleOAuthService $googleOAuth,
        UserRepository $userRepository,
        UserProfilRepository $userProfilRepository,
        UserPreferenceRepository $userPreferenceRepository,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        CookieService $cookieService
    ): Response
    {
        if ($request->query->has('error')){
            $this->addFlash('login_error', 'La connexion avec Google a été annulée.');

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        $state = (string) $request->query->get('state', '');
        $expectedState = (string) $request->getSession()->get(self::SESSION_STATE_KEY, '');
        $request->getSession()->remove(self::SESSION_STATE_KEY);

        if ('' === $state || !hash_equals($expectedState, $state)){
            $this->addFlash('login_error', 'La réponse Google est invalide.');

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        $code = (string) $request->query->get('code', '');

        if ('' === $code){
            $this->addFlash('login_error', 'Google n\'a pas renvoyé de code de connexion.');

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        try {
            $googleProfile = $googleOAuth->getProfileFromAuthorizationCode($code, $this->getCallbackUrl());
        } catch (\Throwable $exception){
            $this->addFlash('login_error', $exception->getMessage());

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        $user = $userRepository->findOneBy(['googleId' => $googleProfile['sub']]);
        $created = false;
        $linked = false;

        if (!$user instanceof User){
            $currentUser = $this->getUser();
            $emailUser = $userRepository->findOneBy(['email' => $googleProfile['email']]);

            if ($currentUser instanceof User){
                if ($emailUser instanceof User && $emailUser->getId() !== $currentUser->getId()){
                    $this->addFlash('login_error', 'Cet email est dÃ©jÃ  rattachÃ© Ã  un autre compte.');

                    return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
                }

                $user = $currentUser;
                $linked = true;
            } elseif ($emailUser instanceof User){
                $this->addFlash('login_error', 'Cet email est dÃ©jÃ  rattachÃ© Ã  un compte. Connectez-vous avant de le rattacher Ã  Google.');

                return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
            } else {
                $user = $this->createUserFromGoogleProfile($googleProfile, $userRepository, $passwordHasher);
                $userProfil = new UserProfil();
                $userProfil->setUser($user);
                $userPreference = new UserPreference();
                $userPreference->setUser($user);

                $userRepository->add($user, true);
                $userProfilRepository->add($userProfil, true);
                $userPreferenceRepository->add($userPreference, true);
                $created = true;
            }

            $user
                ->setGoogleId($googleProfile['sub'])
                ->setGoogleEmail($googleProfile['email'])
                ->setAnonyme(false)
            ;

            if (null === $user->getEmail()){
                $user->setEmail($googleProfile['email']);
            }

            $userRepository->add($user, true);
        }

        if ($created){
            $this->addFlash('success', 'Votre compte a été créé avec Google.');
        } elseif ($linked){
            $this->addFlash('success', 'Votre compte est maintenant rattaché à Google.');
        }

        $response = $security->login($user, 'form_login', 'main', [new RememberMeBadge()])
            ?? $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER)
        ;

        $cookieService->removeAnonymousTestSessionCookies($response);

        return $cookieService->addRegisteredUserCookieForUser($response, $user);
    }

    private function getCallbackUrl(): string
    {
        return $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param array{sub: string, email: string, name: string|null} $googleProfile
     */
    private function createUserFromGoogleProfile(array $googleProfile, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): User
    {
        $user = new User();
        $user
            ->setAnonyme(false)
            ->setRoles(['ROLE_USER'])
            ->setCode($this->createUserCode($userRepository))
            ->setUserName($this->createGoogleUserName($googleProfile['email'], $userRepository))
            ->setEmail($googleProfile['email'])
            ->setGoogleId($googleProfile['sub'])
            ->setGoogleEmail($googleProfile['email'])
        ;
        $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(24))));

        return $user;
    }

    private function createGoogleUserName(string $email, UserRepository $userRepository): string
    {
        $base = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', strstr($email, '@', true) ?: $email));

        if (strlen($base) < 5){
            $base = 'google'.$base;
        }

        $candidate = substr($base, 0, 160);
        $suffix = 1;

        while (null !== $userRepository->findOneBy(['userName' => $candidate])){
            $suffix++;
            $candidate = substr($base, 0, 155).$suffix;
        }

        return $candidate;
    }

    private function createUserCode(UserRepository $userRepository): string
    {
        do {
            $code = $this->randomCode();
        } while (!empty($userRepository->findOneByCode($code)));

        return $code;
    }

    private function randomCode(int $nbCharacter = 8): string
    {
        $comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = [];
        $combLen = strlen($comb) - 1;

        for ($i = 0; $i < $nbCharacter; $i++){
            $n = random_int(0, $combLen);
            $pass[] = $comb[$n];
        }

        return implode($pass);
    }
}
