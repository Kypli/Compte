<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleOAuthService
{
    private const AUTHORIZATION_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(string:GOOGLE_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(string:GOOGLE_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->clientId) && '' !== trim($this->clientSecret);
    }

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $this->assertConfigured();

        return self::AUTHORIZATION_ENDPOINT.'?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array{sub: string, email: string, name: string|null}
     */
    public function getProfileFromAuthorizationCode(string $code, string $redirectUri): array
    {
        $this->assertConfigured();

        $tokenData = $this->httpClient->request('POST', self::TOKEN_ENDPOINT, [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ],
        ])->toArray(false);

        if (!isset($tokenData['access_token']) || !is_string($tokenData['access_token'])){
            throw new \RuntimeException('Google n\'a pas renvoyé de jeton de connexion.');
        }

        $profileData = $this->httpClient->request('GET', self::USERINFO_ENDPOINT, [
            'auth_bearer' => $tokenData['access_token'],
        ])->toArray(false);

        if (!isset($profileData['sub'], $profileData['email']) || !is_string($profileData['sub']) || !is_string($profileData['email'])){
            throw new \RuntimeException('Google n\'a pas renvoyé de profil utilisable.');
        }

        return [
            'sub' => $profileData['sub'],
            'email' => $profileData['email'],
            'name' => isset($profileData['name']) && is_string($profileData['name']) ? $profileData['name'] : null,
        ];
    }

    private function assertConfigured(): void
    {
        if (!$this->isConfigured()){
            throw new \RuntimeException('La connexion Google n\'est pas encore configurée.');
        }
    }
}
