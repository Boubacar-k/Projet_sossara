<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Document;
use App\Form\RegistrationFormType;
use App\Security\AppCustomAuthenticator;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppCustomAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $document = new Document();

        $data = json_decode($request->getContent(), true);

        $user->setNom($data['nom']);
        $user->setEmail($data['email']);
        if (isset($data['roles']) && is_array($data['roles'])){
            $existingRoles = 'ROLE';
            $newRoles = $data['roles'];

            $newRoles = trim($newRoles);

            $concatenatedRoles = $existingRoles.'_'.$newRoles;

            $rolesArray = array_map('trim', $concatenatedRoles);

            $user->setRoles($rolesArray);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user,$data['password']));
        $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        $user->setTelephone($data['telephone']);
        // $user->setPhoto($data['photo_user']);
        $user->setIsCertified(false);
        $user->setIsVerified(false);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdateAt(new \DateTimeImmutable());

        $document->setNumDoc($data['num_doc']);
        $document->setNom($data['nom_doc']);
        $document->setPhoto($data['photo']);
        $document->setUtilisateur($user);
        $user->addDocument($document);
        // $roles[] = 'ROLE_AGENCE';
        // if($user->getRoles()==$roles){
            
        // }

    
        // $user->setNom($request->request->get('nom'));
        // $user->setEmail($request->request->get('email'));
        // $user->setRoles(['ROLE_USER']);
        // $user->setPassword($userPasswordHasher->hashPassword($user,$request->request->get('password')));
        // $user->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        // $user->setTelephone(strval($request->request->get('telephone')));
        // $user->setIsCertified(false);
        // $user->setIsVerified(false);

        if ($request->getMethod() == Request::METHOD_POST){
            try {

                $entityManager->persist($user);
                $entityManager->persist($document);
                $entityManager->flush();
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
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $this->json(['token' => $token,'message' => 'Utilisateur inscrit avec succès'], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur d'inscription",
        ]);
    }

    private function generateJwtToken(UserInterface $user): string
    {
        $payload = ['username' => $user->getEmail(), 'roles' => $user->getRoles()];
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
