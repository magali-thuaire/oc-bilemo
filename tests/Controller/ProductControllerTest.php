<?php

namespace App\Tests\Controller;

use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Tests\Utils\ApiTestCase;

final class ProductControllerTest extends ApiTestCase
{

    public function testGETShow()
    {
        $product = ProductFactory::new()
                   ->createdNow()
                   ->create();

        $this->client->jsonRequest('GET', '/api/products/' . $product->getId());

        $this->assertResponseStatusCodeSame(200);

        $response = $this->client->getResponse();
        $this->assertResponseHasHeader('Location');
        $this->asserter()->assertResponsePropertiesExist(
            $response,
            [
                'id',
                'name',
                'description',
                'price',
            ]
        );
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            'createdAt'
        );
        $this->asserter()->assertResponsePropertyDoesNotExist(
            $response,
            'updatedAt'
        );
        $this->asserter()->assertResponsePropertyEquals(
            $response,
            '_links.self',
            '/api/products/' . $product->getId()
        );
    }

    public function test405Exception()
    {
        $product = ProductFactory::new()
                     ->createdNow()
                     ->create();

        $this->client->jsonRequest('PUT', '/api/products/' . $product->getId());

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

    public function test404Exception()
    {
        $this->client->jsonRequest('GET', '/api/products/fake');

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

    public function testGETList()
    {
        ProductFactory::new()
           ->createdNow()
           ->createMany(40);

        $this->client->jsonRequest('GET', '/api/products');

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
        UserFactory::new()
           ->createdNow()
           ->createMany(40);

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
            40
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
}
