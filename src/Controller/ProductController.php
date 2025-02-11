<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductController extends AbstractController
{
    private $productRepository;
    private $entityManager;
    private $validator;

    public function __construct(ProductRepository $productRepo, EntityManagerInterface $entityMan, ValidatorInterface $validator)
    {
        $this->productRepository = $productRepo;
        $this->entityManager = $entityMan;
        $this->validator = $validator;
    }

    #[Route('/api/products', methods: ['GET'])]
    public function getProducts(): JsonResponse
    {
        $products = $this->productRepository->findAll();
        return $this->json($products);
    }

    #[Route('/api/products/{id}', methods: ['GET'])]
    public function getProduct($id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }
        return $this->json($product);
    }

    #[Route('/api/products', methods: ['POST'])]
    public function createProduct(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description']);
        $product->setStock($data['stock']);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['message' => 'Validation failed', 'errors' => (string)$errors], 400);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, 201);
    }

    #[Route('/api/products/{id}', methods: ['PUT'])]
    public function updateProduct($id, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description']);
        $product->setStock($data['stock']);

        $this->entityManager->flush();

        return $this->json($product);
    }

    #[Route('/api/products/{id}', methods: ['DELETE'])]
    public function deleteProduct($id): JsonResponse
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['message' => 'Product not found'], 404);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(['message' => 'Product deleted'], 200);
    }
}
