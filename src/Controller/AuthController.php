<?php

namespace App\Controller;

use App\Security\UserAuthenticator;
use App\Repository\UserRepository;
use App\DTO\RegisterDTO;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManagerInterface;

final class AuthController extends AbstractController
{

    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

    private EntityManagerInterface $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request, PasswordHasherInterface $encoder, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe sont nécessaires.'], Response::HTTP_BAD_REQUEST);
        }

        $user = new Users();
        $user->setEmail($data['email']);
        $hashedPassword = $encoder->hash($data['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);

        $errors = $validator->validate($user);
        $errorDetails = [];
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $path = $error->getPropertypath();
                $message = $error->getMessage();

                if (!isset($errorDetails[$path])) {
                    $errorDetails[$path] = [];
                }
                $errorDetails[$path][] = $message;
            }
            return $this->json([
                'message' => 'Echec de validation',
                'errors' => $errorDetails
            ], 400);
        }

        $entityManager = $this->entityManager;
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur crée.'], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request, AuthenticatorManagerInterface $authenticationManager): JsonResponse
    {
        $data = $request->getPayload();

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->entityManager->getRepository(Users::class)->findOneByEmail($data['email']);
            if (!$user) {
                return new JsonResponse(['message' => 'Utilisateur introuvable.'], Response::HTTP_UNAUTHORIZED);
            }

            $token = new UsernamePasswordToken($user, $data['password'], 'main', $user->getRoles());
            $authenticationManager->authenticate($token);

            return $this->json(['token' => $this->get('lexik_jwt_authentication.encoder')->encode(['username' => $user->getEmail()])]);
        } catch (AuthenticationException $e) {
            return new JsonResponse(['message' => 'Échec authentification.'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
