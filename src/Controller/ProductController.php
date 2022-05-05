<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products', name: 'api_products_')]
final class ProductController extends AbstractController
{
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json(
            $product,
            Response::HTTP_OK,
            ['Location' => $this->generateUrl('api_products_show', ['id' => $product->getId()])],
            ['groups' => ['product:read']]
        );
    }
}
