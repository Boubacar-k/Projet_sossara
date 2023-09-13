<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Document;
use App\Entity\PhotoDocument;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use App\Security\AppCustomAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use App\Service\FileUploader;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authentication\UserTokenInterface;

#[Route('/api', name: 'api_')]
class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private $userPasswordEncoder;
    private $tokenManager;
    private $tokenStorage;
    private $jwtEncoder;
    
    public function __construct(EmailVerifier $emailVerifier,JWTEncoderInterface $jwtEncoder,
    JWTTokenManagerInterface $tokenManager, TokenStorageInterface $tokenStorage)
    {
        $this->emailVerifier = $emailVerifier;
        $this->tokenManager = $tokenManager;
        $this->tokenStorage = $tokenStorage;
        $this->jwtEncoder = $jwtEncoder;
    }

    #[Route('/register', name: 'app_register', methods: ['POST'],)]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,FileUploader $fileUploader, 
    UrlGeneratorInterface $urlGeneratorInterface, AppCustomAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $document = new Document();

        $data = json_decode($request->getContent(), true);

        $user->setNom($request->request->get('nom'));
        $user->setEmail($request->request->get('email'));
        $newRoles = $request->request->get('roles');

        $newRoles = $newRoles;
    
        if ($newRoles === 'PROPRIETAIRE') {
            $user->setRoles(['ROLE_PROPRIETAIRE']);
        } elseif ($newRoles === 'AGENCE') {
            $user->setRoles(['ROLE_AGENCE']);
        }elseif ($newRoles === 'LOCATAIRE OU ACHETEUR') {
            $user->setRoles(['ROLE_LOCATAIRE']);
        } else {

            return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user,$request->request->get('password')));
        $user->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        $user->setTelephone($request->request->get('telephone'));

        $user->setIsCertified(false);
        $user->setIsVerified(false);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdateAt(new \DateTimeImmutable());

        $document->setNumDoc($request->request->get('num_doc'));
        $document->setNom($request->request->get('nom_doc'));
        $images = $request->files->get('photo');
        if ($images != null) {
            foreach ($images as $image) {
                $imageFileName = $fileUploader->upload($image);
                
                    $photo = new PhotoDocument();
                    $photo->setNom($imageFileName);
                    $photo->setCreatedAt(new \DateTimeImmutable());
                    $photo->setUpdatedAt(new \DateTimeImmutable());
                    $document->addPhotoDocument($photo);
                    $entityManager->persist($photo);
                }
        }
        
        $user->addDocument($document);

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($user);
                $entityManager->persist($document);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            

            // generate a signed url and email it to the user
            $token = $this->generateJwtToken($user);

            $confirmationUrl = $this->generateUrl('api_app_verify_email', [
                'token' => $token,
            ], $urlGeneratorInterface::ABSOLUTE_URL);

            $this->authenticateUser($user, $token);

            $email = (new TemplatedEmail())
                ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
                ->to($user->getEmail())
                ->subject('Veuillez confirmer votre email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'confirmationUrl' => $confirmationUrl,
                ]);

            $this->emailVerifier->sendEmailConfirmation('api_app_verify_email', $user, $email);
            $userInfo = [
                'id' => $user->getId(),
                'username' => $user->getnom(),
                'email' => $user->getEmail(),
                'date_de_naissance' => $user->getDateNaissance(),
                'telephone' => $user->getTelephone(),
                'role' => $user->getRoles(),
                'photo' => $user->getPhoto(),
                'documents' => [],
            ];

            foreach ($user->getDocuments() as $document) {
                $photos = [];
                foreach ($document->getPhotoDocuments() as $photoDocument) {
                    $photos[] = [
                        'id' => $photoDocument->getId(),
                        'nom' => $photoDocument->getNom(),
                    ];
                }
                $documentInfo = [
                    'id' => $document->getId(),
                    'nom' => $document->getNom(),
                    'num_doc'=> $document->getNumDoc(),
                    'photo' => $photos,
                ];
                $userInfo['documents'][] = $documentInfo;
            }

            return $this->json(['token' =>$token,'message' => 'Utilisateur inscrit avec succès'], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur d'inscription",
        ]);
    }

    private function generateJwtToken(UserInterface $user): string
    {
        $payload = [
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'id' => $user->getId(),
        ];
    
        return $this->jwtEncoder->encode($payload);
    }

    private function authenticateUser(UserInterface $user, string $token): void
    {
        $authenticatedToken = new UsernamePasswordToken($user, $token, $user->getRoles());

        $this->tokenStorage->setToken($authenticatedToken);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository, TranslatorInterface $translator, EntityManagerInterface $entityManager): Response
    {
        $token = $request->get('token');
        $user = new User();

        if (!$token) {
            // Le token n'est pas présent dans la requête, renvoyez une réponse d'erreur.
            return $this->json(['message' => 'JWT Token not present'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = $this->jwtEncoder->decode($token);
        } catch (JWTDecodeFailureException $e) {
            // Le token n'est pas valide, renvoyez une réponse d'erreur.
            return $this->json(['message' => 'JWT Token not valid'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy(['email' => $data['username']]);

        if (!$user) {
            throw new AccessDeniedException('User not found');
        }

        if($user && !$user->isVerified()){
            $user->setIsVerified(true);
            $entityManager->persist($user);
            $entityManager->flush();
            
        }
        return $this->redirectToRoute('api_app_angular_redirect');
    }
}
