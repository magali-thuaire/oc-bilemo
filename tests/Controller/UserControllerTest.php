<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Utils\ApiTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserControllerTest extends ApiTestCase
{
    private ?TranslatorInterface $translator;
    private ?UserRepository $userRepository;
    private ?UserPasswordHasherInterface $userPasswodHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->getService('translator.data_collector');
        $this->userRepository = $this->getService('doctrine')->getRepository(User::class);
        $this->userPasswodHasher = $this->getService('security.user_password_hasher');
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

    public function testPOSTNewValidationErrors()
    {
        // 1- not blank password
        $data = [
            'email' => 'post_error@test.fr',
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

        // 2- min password
        $data = [
            'email' => 'post_error@test.fr',
            'password' => 'bile'
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists($response, 'errors.password');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.min', ['{{ limit }}' => 6], 'validators')
        );
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

    public function testPUTUpdate()
    {
        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'put@test.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $data = [
            'email' => 'put_update@test.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('PUT', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertiesExist($response, [
            'email'
        ]);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'put_update@test.fr'
        );
        $updatedUser = $this->userRepository->find($user->getId());
        $this->assertTrue($this->userPasswodHasher->isPasswordValid($updatedUser, 'mobile'));
    }

    public function testPATCHUpdate()
    {
        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'patch@test.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $data = [
            'email' => 'patch_update@test.fr',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertiesExist($response, [
            'email'
        ]);
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'patch_update@test.fr'
        );
    }

    public function testPATCHUpdateValidationErrors()
    {
        // 1- min password
        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'patch_error@test.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $data = [
            'password' => 'test',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists($response, 'errors.password');
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.min', ['{{ limit }}' => 6], 'validators')
        );
    }

    public function testDELETERemove()
    {
        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'delete@test.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $this->client->jsonRequest('DELETE', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(204);

        $response = $this->client->getResponse();
        $this->assertEmpty($response->getContent());

        $removedUser = $this->userRepository->find($user->getId());
        $this->assertEmpty($removedUser);
    }
}
