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

    // Response 200 - OK
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

    // Response 401 - Unauthorized
    public function testUserGETList401Exception()
    {
        $this->client->jsonRequest('GET', '/api/users');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 404 - Not Found
    public function testUserGETListPaginated404Exception()
    {
        $client = $this->setAuthorizedClient();

        $this->client->jsonRequest('GET', '/api/users?page=100');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testUserGETListPaginated405Exception()
    {
        $client = $this->setAuthorizedClient();

        $this->client->jsonRequest('PUT', '/api/users');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 201 - Created
    public function testUserPOSTNew()
    {
        $this->setAuthorizedClient();

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
    }

    // Response 400 - Bad Request - Validation Errors
    public function testUserPOSTNew400Exception()
    {
        $this->setAuthorizedClient();

        // 1- not blank password
        $data = [
            'email' => 'test@bilemo.fr',
            'password' => ''
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $response = $this->client->getResponse();

        $this->asserter()->assertValidationErrorsException($response, 400);

        $this->asserter()->assertResponsePropertyExists(
            $response,
            'errors.password'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors.password[0]',
            $this->translator->trans('user.password.not_blank', [], 'validators')
        );
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            'errors.email'
        );

        // 2- min password
        $data = [
            'email' => 'test@bilemo.fr',
            'password' => 'mo'
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $this->asserter()->assertValidationErrorsException($response, 400);

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

    // Response 401 - Unauthorized
    public function testUserPOSTNew401Exception()
    {
        $data = [
            'email' => 'test@bilemo.fr',
            'password' => ''
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 405 - Method Not Allowed
    public function testUser405Exception()
    {
        $this->client->jsonRequest('PUT', '/api/users');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 415 - Unsupported Media Type
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

        $response = $this->client->getResponse();
        $this->asserter()->assert415Exception($response);
    }

    // Response 422 - Unprocessable Entity
    public function testUserPOSTNew422Exception()
    {
        $this->setAuthorizedClient();

        $data = [
            'email' => 'test@bilemo.fr',
            'password' => 'bilemo',
        ];

        $this->client->jsonRequest('POST', '/api/users', $data);
        $this->client->jsonRequest('POST', '/api/users', $data);

        $response = $this->client->getResponse();
        $this->asserter()->assert422Exception($response);

        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors',
            $this->translator->trans('user.email.unique', [], 'validators')
        );
    }

    // Response 200 - OK
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

    // Response 401 - Unauthorized
    public function testUserGETShow401Exception()
    {
        $user = $this->createUser();
        $showUserId = $user->getId();

        $this->client->jsonRequest('GET', '/api/users/' . $showUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 403 - Forbidden
    public function testUserGETShow403Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUser();
        $showUserId = $user->getId();

        $this->client->jsonRequest('GET', '/api/users/' . $showUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 403);

    }

    // Response 404 - Not Found
    public function testUserGETShow404Exception()
    {
        $this->client->jsonRequest('GET', '/api/users/fake');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testUserGETShow405Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $showUserId = $user->getId();

        $this->client->jsonRequest('POST', '/api/users/' . $showUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 200 - OK
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

    // Response 401 - Unauthorized
    public function testUserPUTUpdate401Exception()
    {
        $user = $this->createUser();
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('PUT', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 403 - Forbidden
    public function testUserPUTUpdate403Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUser();
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('PUT', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 403);
    }

    // Response 404 - Not Found
    public function testUserPUTUpdate404Exception()
    {
        $this->client->jsonRequest('PUT', '/api/users/fake');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testUserPUTUpdate405Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr',
            'password' => 'mobile'
        ];
        $this->client->jsonRequest('POST', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 415 - Unsupported Media Type
    public function testUserPUTUpdate415Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $invalidJson = <<<EOF
[
            'email' => 'test@bilemo.fr
}
EOF;

        $this->client->jsonRequest('PUT', '/api/users/' . $updateUserId, [$invalidJson]);

        $response = $this->client->getResponse();
        $this->asserter()->assert415Exception($response);
    }

    // Response 422 - Unprocessable Entity
    public function testUserPUTUpdate422Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'authorized@bilemo.fr',
            'password' => 'bilemo',
        ];

        $this->client->jsonRequest('PUT', '/api/users/' . $user->getId(), $data);

        $response = $this->client->getResponse();
        $this->asserter()->assert422Exception($response);

        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors',
            $this->translator->trans('user.email.unique', [], 'validators')
        );
    }

    // Response 200 - OK
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

    // Response 400 - Bad Request - Validation Errors
    public function testUserPATCHUpdate400Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        // 1- min password

        $data = [
            'password' => 'patch',
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertValidationErrorsException($response, 400);

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

    // Response 401 - Unauthorized
    public function testUserPATCHUpdate401Exception()
    {
        $user = $this->createUser();
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr'
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 403 - Forbidden
    public function testUserPATCHUpdate403Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUser();
        $updateUserId = $user->getId();

        $data = [
            'email' => 'update@bilemo.fr'
        ];
        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 403);
    }

    // Response 404 - Not Found
    public function testUserPATCHUpdate404Exception()
    {
        $this->client->jsonRequest('PATCH', '/api/users/fake');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testUserPATCHUpdate405Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'patch@bilemo.fr',
        ];
        $this->client->jsonRequest('POST', '/api/users/' . $updateUserId, $data);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 415 - Unsupported Media Type
    public function testUserPATCHUpdate415Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $invalidJson = <<<EOF
[
            'email' => 'test@bilemo.fr
}
EOF;

        $this->client->jsonRequest('PATCH', '/api/users/' . $updateUserId, [$invalidJson]);

        $response = $this->client->getResponse();
        $this->asserter()->assert415Exception($response);
    }

    // Response 422 - Unprocessable Entity
    public function testUserPATCHUpdate422Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $updateUserId = $user->getId();

        $data = [
            'email' => 'authorized@bilemo.fr'
        ];

        $this->client->jsonRequest('PATCH', '/api/users/' . $user->getId(), $data);

        $response = $this->client->getResponse();
        $this->asserter()->assert422Exception($response);

        $this->asserter()->assertResponsePropertyEquals(
            $response,
            'errors',
            $this->translator->trans('user.email.unique', [], 'validators')
        );
    }

    // Response 204 - No Content
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

    // Response 401 - Unauthorized
    public function testUserDELETERemove401Exception()
    {
        $user = $this->createUser();
        $deleteUserId = $user->getId();

        $this->client->jsonRequest('DELETE', '/api/users/' . $deleteUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 403 - Forbidden
    public function testUserDELETERemove403Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUser();
        $deleteUserId = $user->getId();

        $this->client->jsonRequest('DELETE', '/api/users/' . $deleteUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 403);
    }

    // Response 404 - Not Found
    public function testUserDELETERemove404Exception()
    {
        $client = $this->setAuthorizedClient();

        $this->client->jsonRequest('DELETE', '/api/users/fake');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testUserDELETERemove405Exception()
    {
        $client = $this->setAuthorizedClient();

        $user = $this->createUserOwnedByClient($client);
        $deleteUserId = $user->getId();

        $this->client->jsonRequest('POST', '/api/users/' . $deleteUserId);

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
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

        UserFactory::new()
                   ->createdNow()
                   ->setClient($client)
                   ->createMany($nb);
    }

    private function createUser(): User|Proxy
    {
        $client = $this->createApiClient();

        return UserFactory::new()
                          ->createdNow()
                          ->setClient($client)
                          ->create();
    }

}
