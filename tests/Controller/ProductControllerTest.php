<?php

namespace App\Tests\Controller;

use App\Factory\ProductFactory;
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
}
