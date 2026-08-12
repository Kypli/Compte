<?php

namespace App\Tests\Service;

use App\Service\GoogleOAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GoogleOAuthServiceTest extends TestCase
{
    public function testAuthorizationUrlUsesGoogleOAuthParameters(): void
    {
        $service = new GoogleOAuthService(new MockHttpClient(), 'client-id', 'client-secret');
        $url = $service->getAuthorizationUrl('http://compte/connect/google/check', 'state-token');

        self::assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        self::assertStringContainsString('client_id=client-id', $url);
        self::assertStringContainsString('redirect_uri=http%3A%2F%2Fcompte%2Fconnect%2Fgoogle%2Fcheck', $url);
        self::assertStringContainsString('response_type=code', $url);
        self::assertStringContainsString('scope=openid%20email%20profile', $url);
        self::assertStringContainsString('state=state-token', $url);
    }

    public function testProfileCanBeReadFromAuthorizationCode(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"access_token":"access-token"}', [
                'response_headers' => ['content-type: application/json'],
            ]),
            new MockResponse('{"sub":"google-user-id","email":"pierre@example.com","name":"Pierre"}', [
                'response_headers' => ['content-type: application/json'],
            ]),
        ]);
        $service = new GoogleOAuthService($client, 'client-id', 'client-secret');

        self::assertSame([
            'sub' => 'google-user-id',
            'email' => 'pierre@example.com',
            'name' => 'Pierre',
        ], $service->getProfileFromAuthorizationCode('code', 'http://compte/connect/google/check'));
    }

    public function testServiceIsNotConfiguredWithoutCredentials(): void
    {
        $service = new GoogleOAuthService(new MockHttpClient(), '', '');

        self::assertFalse($service->isConfigured());
    }
}
