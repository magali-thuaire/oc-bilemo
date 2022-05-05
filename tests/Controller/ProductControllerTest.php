<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Factory\ProductFactory;
use App\Tests\Utils\ApiTestCase;
use Zenstruck\Foundry\Proxy;

final class ProductControllerTest extends ApiTestCase
{
    public function testGETShow()
    {
        $this->setAuthorizedClient();

        $product = $this->createProduct();

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
        $product = $this->createProduct();

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
        $this->setAuthorizedClient();

        $this->createProducts(40);

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
        $this->setAuthorizedClient();

        $this->createProducts(40);

        $this->client->jsonRequest('GET', '/api/products');

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
        $this->client->jsonRequest('GET', '/api/products?order=asc&filter=a');

        $response = $this->client->getResponse();

        $this->asserter()->assertResponsePropertyContains(
            $response,
            '_links.last',
            '?order=asc&filter=a'
        );
    }

    private function createProduct(): Product|Proxy
    {
        return ProductFactory::new()
                    ->createdNow()
                    ->create();
    }

    private function createProducts(int $nb = 20): void
    {
        ProductFactory::new()
             ->createdNow()
             ->createMany($nb);
    }

}
