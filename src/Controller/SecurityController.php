<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

#[Route(path: '/api', name: 'api_')]
class SecurityController extends AbstractController
{
    private $userPasswordEncoder;
    private $tokenManager;
    private $tokenStorage;
    private $jwtEncoder;

    public function __construct(JWTEncoderInterface $jwtEncoder,
    JWTTokenManagerInterface $tokenManager, TokenStorageInterface $tokenStorage)
    {
        $this->tokenManager = $tokenManager;
        $this->tokenStorage = $tokenStorage;
        $this->jwtEncoder = $jwtEncoder;
    }
    #[Route(path: '/login', name: 'app_login')]

    // public function login(Request $request, JWTTokenManagerInterface $JWTManager): Response
    // {
    //     // Récupérer les informations d'identification de l'utilisateur à partir de la requête
    //     $credentials = json_decode($request->getContent(), true);

    //     // Valider les informations d'identification (par exemple, nom d'utilisateur et mot de passe)
    //     // et récupérer l'utilisateur correspondant depuis votre système de stockage utilisateur
    //     // Ici, nous supposons que vous avez un service UserService qui gère cela

    //     $user = $this->get('App\Service\UserService')->getUserByCredentials($credentials);

    //     if (!$user) {
    //         return new Response('Utilisateur introuvable ou informations d\'identification incorrectes.', Response::HTTP_UNAUTHORIZED);
    //     }

    //     // Générer le token JWT
    //     $token = $JWTManager->create($user);

    //     // Retourner le token dans la réponse
    //     return new Response(json_encode(['token' => $token]), Response::HTTP_OK);
    // }

    public function login(Request $request,JWTTokenManagerInterface $JWTManager, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $data = json_decode($request->getContent(), true);
        $email = trim($data['email']);
        $password = trim($data['password']);

        $user = $userRepository->findOneBy(['email' => $email]);

        // $user = $this->get('App\Service\UserService')->getUserByCredentials($credentials);

        if (!$user) {
            return $this->json(['etat' => false, 'message' => 'Email ou Mot de passe incorrecte'], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['etat' => false, 'message' => 'Email ou Mot de passe incorrecte'], Response::HTTP_UNAUTHORIZED);
        }

        $userInfo = [
            'id' => $user->getId(),
            'username' => $user->getnom(),
            'email' => $user->getEmail(),
            'date de naissance' => $user->getDateNaissance(),
            'telephone' => $user->getTelephone(),
        ];
        $token = $this->generateJwtToken($user);

        $this->authenticateUser($user, $token);
        return $this->json(['etat' => true,'user'=> $userInfo, 'message' => 'Connexion reussie','token' => $token], Response::HTTP_OK);
    }


    private function generateJwtToken(UserInterface $user): string
    {
        $payload = ['email' => $user->getUserIdentifier(), 'password' => $user->getRoles()];
        return $this->jwtEncoder->encode($payload);
    }

    private function authenticateUser(UserInterface $user, string $token): void
    {
        $authenticatedToken = new UsernamePasswordToken($user, $token, $user->getRoles());

        $this->tokenStorage->setToken($authenticatedToken);
    }


    // public function login(AuthenticationUtils $authenticationUtils): Response
    // {
    //     // if ($this->getUser()) {
    //     //     return $this->redirectToRoute('target_path');
    //     // }

    //     // get the login error if there is one
    //     $error = $authenticationUtils->getLastAuthenticationError();
    //     // last username entered by the user
    //     $lastUsername = $authenticationUtils->getLastUsername();

    //     return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    //     // return $this->json(['last_username' => $lastUsername, 'error' => $error]);
    // }

    #[Route(path: '/logout', name: 'app_logout')]

    public function logout(Request $request, JWTTokenManagerInterface $JWTManager): Response
    {
        
        return new Response('Vous avez été déconnecté.', Response::HTTP_OK);
    }

    // public function logout(): void
    // {
    //     throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    // }
}
