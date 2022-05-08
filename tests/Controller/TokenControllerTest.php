<?php

namespace App\Tests\Controller;

use App\Tests\Utils\ApiTestCase;

final class TokenControllerTest extends ApiTestCase
{
    // Response 200 - OK
    public function testTokenPOSTCreate()
    {
        $this->createApiClient();

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

    // Response 400 - Bad Request
    public function testTokenPOSTCreate400Exception()
    {
        $data = [
            'email' => 'test@bilemo.fr'
        ];

        $this->client->jsonRequest('POST', '/api/tokens', $data);

        $this->assertResponseStatusCodeSame(400);

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
            'Bad Request'
        );
    }

    // Response 401 - Unauthorized : Invalid Credentials
    public function testTokenPOSTCreate401Exception()
    {
        $data = [
            'email' => 'test@bilemo.fr',
            'password' => 'bilemo'
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

    // Response 405 - Method Not Allowed
    public function testTokenPOSTCreate405Exception()
    {

        $this->client->jsonRequest('GET', '/api/tokens');
        $this->assertResponseStatusCodeSame(405);

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
            'Method Not Allowed'
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
