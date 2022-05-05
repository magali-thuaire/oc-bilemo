<?php

namespace App\Tests\Controller;

use App\Factory\UserFactory;
use App\Tests\Utils\ApiTestCase;

final class UserControllerTest extends ApiTestCase
{
    private object $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->getService('translator.data_collector');
    }

    public function testPOSTNew()
    {
        // 1- First request
        $data = [
               'email' => 'post@test.fr',
               'password' => 'bilemo',
        ];
        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(201);

        $response = $this->client->getResponse();
        $this->assertResponseHasHeader('Location');

        $this->asserter()->assertResponsePropertiesExist($response, [
            'id',
            'email',
            'createdAt',
            'updatedAt'
        ]);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'post@test.fr'
        );

        // 2- Second request with same data
        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(422);

        $response = $this->client->getResponse();
        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'This entity already exists in the application'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors',
            $this->translator->trans('user.email.unique', [], 'validators')
        );
    }

    public function testValidationErrors()
    {
        $data = [
            'email' => 'post@test.fr',
            'password' => ''
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertiesExist($response, [
            'type',
            'title',
            'errors'
        ]);
        $this->asserter()->assertResponsePropertyExists($response, 'errors.password');
        $this->asserter()->assertResponsePropertyContains(
            $response,
            'type',
            'validation_errors'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'title',
            'There was validation errors'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.not_blank', [], 'validators')
        );
        $this->asserter()->assertResponsePropertyDoesNotExist($response, 'errors.email');
        $this->assertEquals('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function testInvalidJson()
    {

        $invalidJson = <<<EOF
[
            'email' => 'post@test.fr
            'password' => 'bilemo'
}
EOF;

        $this->client->jsonRequest('POST', '/api/users', [$invalidJson]);

        $this->assertResponseStatusCodeSame(415);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyContains(
            $response,
            'type',
            'invalid_body_format'
        );
    }

    public function test405Exception()
    {
        $this->client->jsonRequest('PUT', '/api/users');

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

    public function testGETShow()
    {
        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'get@test.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $this->client->jsonRequest('GET', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertiesExist($response, [
            'id',
            'email',
            'createdAt',
            'updatedAt'
        ]);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'get@test.fr'
        );
    }

    public function test404Exception()
    {
        $this->client->jsonRequest('GET', '/api/users/fake');

        $this->assertResponseStatusCodeSame(404);

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
            'Not Found'
        );
    }
}
