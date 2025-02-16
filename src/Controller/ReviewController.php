<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review')]
    public function index(): Response
    {
        return $this->render('review/index.html.twig', [
            'controller_name' => 'ReviewController',
        ]);
    }

    private $reviewRepository;
    private $productRepository;

    public function __construct(ReviewRepository $reviewRepository, ProductRepository $productRepository)
    {
        $this->reviewRepository = $reviewRepository;
        $this->productRepository = $productRepository;
    }

    #[Route('', methods: ['GET'])]
    public function getReviews(int $productId): JsonResponse
    {
        $reviews = $this->reviewRepository->findBy(['product' => $productId]);
        return $this->json($reviews);
    }

    #[Route('', methods: ['POST'])]
    public function createReview(int $productId, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($productId);
        
        if (!$product) {
            return $this->json(['error' => 'Porduit non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $review = new Review();
        $review->setProduct($product);
        $review->setUser($this->getUser());
        $review->setRating($data['rating']);
        $review->setComment($data['comment']);
        $this->reviewRepository->save($review, true);

        return $this->json($review, JsonResponse::HTTP_CREATED);
    }
}
