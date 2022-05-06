<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Tests\Utils\ApiTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zenstruck\Foundry\Proxy;

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

    public function testUserPOSTNew()
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

    public function testUserPOSTNewValidationErrors()
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

    public function testUserPOSTNew415Exception()
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

    public function testUser405Exception()
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

    public function testUserGETShow()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $showUserId = $user->getId();

        $this->client->jsonRequest('GET', '/api/users/' . $showUserId);

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
            '/api/users/' . $showUserId
        );
    }

    public function testUserGETShow404Exception()
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

    public function testUserPUTUpdate()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('PUT', '/api/users/' . $updateUserId, $data);

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
        $updatedUser = $this->userRepository->find($updateUserId);
        $this->assertTrue($this->userPasswodHasher->isPasswordValid($updatedUser, 'mobile'));
    }

    public function testUserPATCHUpdate()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'patch@bilemo.fr',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, $data);

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

    public function testUserPATCHUpdateValidationErrors()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        // 1- min password

        $data = [
            'password' => 'patch',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, $data);

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

    public function testUserDELETERemove()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $deleteUserId = $user->getId();

        $this->client->jsonRequest('DELETE', '/api/users/' . $deleteUserId);

        $this->assertResponseStatusCodeSame(204);

        $response = $this->client->getResponse();
        $this->assertEmpty($response->getContent());

        $removedUser = $this->userRepository->find($deleteUserId);
        $this->assertEmpty($removedUser);
    }

    public function testUserGETList()
    {
        $client = $this->setAuthorizedClient();

        $this->createUsersOwnedByClient(10, $client);

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

        // cache
        $this->assertEquals(
            'public',
            $response->headers->get('Cache-Control')
        );
    }

    public function testUserGETListPaginated()
    {
        $client = $this->setAuthorizedClient();

        $this->createUsersOwnedByClient(20, $client);
        $this->createUsers(30);

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

    private function createUsersOwnedByClient(int $nb, Proxy $client): void
    {
        $users = UserFactory::new()
                    ->createdNow()
                    ->setClient($client)
                    ->createMany($nb)
        ;
    }

    private function createUserOwnedByClient(Proxy $client, string $email = 'test@bilemo.fr', string $password = 'bilemo'
    ): Proxy|User
    {
        return UserFactory::new()
                    ->withAttributes([
                        'email' => $email,
                        'password' => $password
                    ])
                    ->createdNow()
                    ->setClient($client)
                    ->create();
    }

    private function createUsers(int $nb): void
    {
        $client = $this->createApiClient();

        $users = UserFactory::new()
                   ->createdNow()
                   ->setClient($client)
                   ->createMany($nb);
    }

}
