<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Document;
use App\Entity\PhotoDocument;
use App\Form\RegistrationFormType;
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
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher,FileUploader $fileUploader, UrlGeneratorInterface $urlGeneratorInterface, AppCustomAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
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

            $token = $this->generateJwtToken($user);

            $this->authenticateUser($user, $token);

            // generate a signed url and email it to the user
            
            $this->emailVerifier->sendEmailConfirmation('api_app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->html('<a href="' . $this->generateUrl('angular_redirect', [], $urlGeneratorInterface::ABSOLUTE_URL) . '">Click here to confirm your email</a>')
                    // ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            $userInfo = [
                'id' => $user->getId(),
                'username' => $user->getnom(),
                'email' => $user->getEmail(),
                'date de naissance' => $user->getDateNaissance(),
                'telephone' => $user->getTelephone(),
                'photo' => $user->getPhoto()
            ];

            $user->setIsVerified(true); // Set the is_verified field to true

            $entityManager->persist($user);
            $entityManager->flush();


            return $this->json(['token' => $token,'user'=> $userInfo,'message' => 'Utilisateur inscrit avec succès'], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur d'inscription",
        ]);
    }

    private function generateJwtToken(UserInterface $user): string
    {
        $payload = ['username' => $user->getUserIdentifier(), 'roles' => $user->getRoles()];
        return $this->jwtEncoder->encode($payload);
    }

    private function authenticateUser(UserInterface $user, string $token): void
    {
        $authenticatedToken = new UsernamePasswordToken($user, $token, $user->getRoles());

        $this->tokenStorage->setToken($authenticatedToken);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('api_app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('api_app_login');
    }
}
