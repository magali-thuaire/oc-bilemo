<?php

namespace App\Tests\Controller;

use App\Tests\Utils\ApiTestCase;

final class TokenControllerTest extends ApiTestCase
{
    public function testTokenPOSTCreate()
    {
        $user = $this->createApiClient();

        $data = [
            'email' => 'test@bilemo.fr',
            'password' => 'bilemo'
        ];

        $this->client->jsonRequest('POST', '/api/tokens', $data);

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'token'
        );
    }

    public function testTokenRequiresAuthentication()
    {
        $this->client->jsonRequest('GET', '/api/users');

        $this->assertResponseStatusCodeSame(401);
        $response = $this->client->getResponse();

        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Unauthorized'
        );

        $this->client->jsonRequest('GET', '/api/products');

        $this->assertResponseStatusCodeSame(401);
        $response = $this->client->getResponse();

        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Unauthorized'
        );
    }

    public function testTokenPOSTInvalidCredentials()
    {
        $user = $this->createApiClient();

        $data = [
            'email' => 'test@bilemo.fr',
            'password' => ''
        ];

        $this->client->jsonRequest('POST', '/api/tokens', $data);
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Unauthorized'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'detail',
            'Invalid credentials.'
        );
    }

    public function testTokenBadToken()
    {
        $wrongAuthorization = 'Bearer WRONG';
        $this->client->setServerParameter('HTTP_AUTHORIZATION', $wrongAuthorization);

        $this->client->jsonRequest('GET', '/api/users');

        $this->assertResponseStatusCodeSame(401);
        $response = $this->client->getResponse();

        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'type',
            'about:blank'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'Unauthorized'
        );
    }
}
