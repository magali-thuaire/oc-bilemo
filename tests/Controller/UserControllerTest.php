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
        $this->setAuthorizedClient();

        // 1- First request
        $data = [
               'email' => 'test@bilemo.fr',
               'password' => 'bilemo',
        ];
        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(201);

        $response = $this->client->getResponse();
        $this->assertResponseHasHeader('Location');

        $this->asserter()->assertResponsePropertiesExist(
            $response,
            [
                'id',
                'email',
                'createdAt',
                'updatedAt'
            ]
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'test@bilemo.fr'
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
        $this->setAuthorizedClient();

        // 1- not blank password
        $data = [
            'email' => 'post_error@bilemo.fr',
            'password' => ''
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertiesExist(
            $response,
            [
                'type',
                'title',
                'errors'
            ]
        );
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'errors.password'
        );
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
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            'errors.email');
        $this->assertEquals(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );

        // 2- min password
        $data = [
            'email' => 'post_error@bilemo.fr',
            'password' => 'bile'
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'errors.password'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.min', ['{{ limit }}' => 6], 'validators')
        );
    }

    public function testInvalidJson()
    {
        $this->setAuthorizedClient();

        $invalidJson = <<<EOF
[
            'email' => 'test@bilemo.fr
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
        $this->setAuthorizedClient();

        $user = UserFactory::new()
                   ->withAttributes([
                       'email' => 'test@bilemo.fr',
                       'password' => 'bilemo'
                   ])
                   ->createdNow()
                   ->create();

        $this->client->jsonRequest('GET', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->assertResponseHasHeader('Location');
        $this->asserter()->assertResponsePropertiesExist(
            $response,
            [
                'id',
                'email',
                'createdAt',
                'updatedAt'
            ]
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'test@bilemo.fr'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            '/api/users/' . $user->getId()
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
        $this->setAuthorizedClient();

        $user = $this->createUser();

        $data = [
            'email' => 'update@bilemo.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('PUT', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'email'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'update@bilemo.fr'
        );
        $updatedUser = $this->userRepository->find($user->getId());
        $this->assertTrue($this->userPasswodHasher->isPasswordValid($updatedUser, 'mobile'));
    }

    public function testPATCHUpdate()
    {
        $this->setAuthorizedClient();

        $user = $this->createUser();

        $data = [
            'email' => 'patch@bilemo.fr',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'email'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'email',
            'patch@bilemo.fr'
        );
    }

    public function testPATCHUpdateValidationErrors()
    {
        $this->setAuthorizedClient();

        // 1- min password
        $user = $this->createUser();

        $data = [
            'password' => 'patch',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $user->getId(), $data);

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'errors.password'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.min', ['{{ limit }}' => 6], 'validators')
        );
    }

    public function testDELETERemove()
    {
        $this->setAuthorizedClient();

        $user = $this->createUser();

        $this->client->jsonRequest('DELETE', '/api/users/' . $user->getId());

        $this->assertResponseStatusCodeSame(204);

        $response = $this->client->getResponse();
        $this->assertEmpty($response->getContent());

        $removedUser = $this->userRepository->find($user->getId());
        $this->assertEmpty($removedUser);
    }

    public function testGETList()
    {
        $this->setAuthorizedClient();

        $this->createUsers(10);

        $this->client->jsonRequest('GET', '/api/users');

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'items'
        );
        $this->asserter()->assertResponsePropertyIsArray(
            $response,
            'items'
        );
    }

    public function testGETListPaginated()
    {
        $this->setAuthorizedClient();

        $this->createUsers(20);

        $this->client->jsonRequest('GET', '/api/users');

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'count'
            , 5
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'total',
            21
        );
        $this->asserter()->assertResponsePropertyExists(
            $response,
            '_links.next'
        );
        $this->asserter()->assertResponsePropertyExists(
            $response,
            '_links.last'
        );
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            '_links.prev'
        );

        // next Url
        $nextUrl = $this->asserter()->readResponseProperty(
            $response,
            '_links.next'
        );

        $this->client->jsonRequest('GET', $nextUrl);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyExists(
            $response,
            '_links.prev'
        );

        // last Url
        $lastUrl = $this->asserter()->readResponseProperty(
            $response,
            '_links.last'
        );

        $this->client->jsonRequest('GET', $lastUrl);

        $response = $this->client->getResponse();
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            '_links.next'
        );

        // filtering
        $this->client->jsonRequest('GET', '/api/users?order=asc&filter=a');

        $response = $this->client->getResponse();

        $this->asserter()->assertResponsePropertyContains(
            $response,
            '_links.last',
            '?order=asc&filter=a'
        );
    }

    private function createUsers(int $nb): void
    {
        UserFactory::new()
           ->createdNow()
           ->createMany($nb);
    }

}
