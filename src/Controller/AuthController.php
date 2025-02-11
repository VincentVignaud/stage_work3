<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\UserAuthenticator;
use App\Repository\UserRepository;
use App\DTO\RegisterDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

final class AuthController extends AbstractController
{

    #[Route('/auth', name: 'app_auth')]
    public function index(): Response
    {
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }

    #[Route('/auth/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe sont nécessaires.'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($encoder->encodePassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);
        
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => 'Entrée incorrect.'], Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur crée.'], Response::HTTP_CREATED);
    }

    #[Route('/api/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request, AuthentificationManagerInterface $authenticationManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['message' => 'Email et mot de passe requis.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->getDoctrine()->getRepository(User::class)->findOneByEmail($data['email']);
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

