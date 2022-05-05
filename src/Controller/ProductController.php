<?php

namespace App\Controller;

use App\Entity\Product;
use App\Manager\ProductManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products', name: 'api_products_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProductController extends AbstractController
{
    private ProductManager $productManager;

    public function __construct(ProductManager $productManager)
    {
        $this->productManager = $productManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $product = $this->productManager->list($request);

        return $this->json(
            $product,
            Response::HTTP_OK,
            [],
            ['groups' => ['product:read']]
        );
    }

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
