<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Factory\ProductFactory;
use App\Tests\Utils\ApiTestCase;
use Zenstruck\Foundry\Proxy;

final class ProductControllerTest extends ApiTestCase
{
    // Response 200 - OK
    public function testProductGETList()
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

        // cache
        $this->assertEquals(
            'public',
            $response->headers->get('Cache-Control')
        );
    }

    public function testProductGETListPaginated()
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

    // Response 401 - Unauthorized
    public function testProductGETList401Exception()
    {
        $this->client->jsonRequest('GET', '/api/products');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 404 - Not Found
    public function testProductGETListPaginated404Exception()
    {
        $this->setAuthorizedClient();

        $this->createProducts(10);

        // error page
        $this->client->jsonRequest('GET', '/api/products?page=100');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testProductGETList405Exception()
    {
        $this->client->jsonRequest('PUT', '/api/products');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
    }

    // Response 200 - OK
    public function testProductGETShow()
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

    // Response 401 - Unauthorized
    public function testProductGETShow401Exception()
    {
        $product = $this->createProduct();

        $this->client->jsonRequest('GET', '/api/products/' . $product->getId());

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 401);
    }

    // Response 404 - Not Found
    public function testProductGETShow404Exception()
    {
        $this->client->jsonRequest('GET', '/api/products/fake');

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 404);
    }

    // Response 405 - Method Not Allowed
    public function testProductGETShow405Exception()
    {
        $product = $this->createProduct();

        $this->client->jsonRequest('PUT', '/api/products/' . $product->getId());

        $response = $this->client->getResponse();
        $this->asserter()->assertHttpException($response, 405);
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
