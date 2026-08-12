<?php

namespace App\Service;

class UserRegistrationValidator
{
	public function getRegistrationError(string $username, string $password): ?string
	{
		if (strlen($username) < 5){
			return 'Le login doit contenir au minimum 5 caractères.';
		}

		if (strlen($password) < 6){
			return 'Le mot de passe doit contenir au minimum 6 caractères.';
		}

		if (!preg_match('/[A-Z]/', $password)){
			return 'Le mot de passe doit contenir au minimum 1 majuscule.';
		}

		if (!preg_match('/[a-z]/', $password)){
			return 'Le mot de passe doit contenir au minimum 1 minuscule.';
		}

		if (!preg_match('/\d/', $password)){
			return 'Le mot de passe doit contenir au minimum 1 chiffre.';
		}

		return null;
	}

	public function getRegistrationCompletionError(string $email, string $password, string $passwordConfirm): ?string
	{
		if ('' === trim($email)){
			return 'Merci de renseigner un email.';
		}

		if (false === filter_var($email, FILTER_VALIDATE_EMAIL)){
			return 'Merci de renseigner un email valide.';
		}

		if ('' === $passwordConfirm){
			return 'Merci de confirmer le mot de passe.';
		}

		if ($password !== $passwordConfirm){
			return 'Les mots de passe ne correspondent pas.';
		}

		return null;
	}
}
