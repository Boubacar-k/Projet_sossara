<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('api/reset/pass')]
class ResetPassController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {
    }
    #[Route('', name: 'api_app_reset_pass')]
    public function index(Request $request,JWTTokenManagerInterface $jwtManager, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $data = json_decode($request->getContent(), true);

        if ($request->getMethod() == Request::METHOD_POST) {
            return $this->processSendingPasswordResetEmail(
                $data['email'],
                $jwtManager,
                $mailer,
                $translator
            );
        }

        return $this->json(['message' => 'erreur']);
    }

    #[Route('/user/check-email', name: 'api_user_check_email')]
    public function checkUserEmail(): Response
    {
        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->json([
            'resetToken' => $resetToken,
        ]);

    }

    #[Route('/user/reset/{token}', name: 'api_user_reset_password')]
    public function userReset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response
    {
        // if ($token) {
        //     // We store the token in session and remove it from the URL, to avoid the URL being
        //     // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
        //     // $this->storeTokenInSession($token);

        //     return $this->redirectToRoute('api_app_reset_password');
        // }

        // $token = $this->getTokenFromSession();
        $token = $request->get('token');

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->json(['reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            )]);

            return $this->redirectToRoute('api_app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        // $form = $this->createForm(ChangePasswordFormType::class);
        // $form->handleRequest($request);

        $data = json_decode($request->getContent(), true);
        

        if ($request->getMethod() == Request::METHOD_POST) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $user,
                $data['password'],
            );

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            // The session is cleaned up after the password has been changed.
            // $this->cleanSessionAfterReset();

            return $this->json([
                'message' => "mot de passe modifie avec succes",
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Le formulaire est invalide.',
        ], 400);
    }

    private function processSendingPasswordResetEmail(string $emailFormData,JWTTokenManagerInterface $jwtManager, MailerInterface $mailer, TranslatorInterface $translator): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return new JsonResponse(['message' => 'No user found with this email.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // If you want to tell the user why a reset email was not sent, uncomment
            // the lines below and change the redirect to 'app_forgot_password_request'.
            // Caution: This may reveal if a user is registered or not.
            //
            // $this->addFlash('reset_password_error', sprintf(
            //     '%s - %s',
            //     $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE, [], 'ResetPasswordBundle'),
            //     $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            // ));

            return $this->json(['message' => 'Error generating reset token.'], Response::HTTP_BAD_REQUEST);
        }

        
        $email = (new TemplatedEmail())
            ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('reset_password/email_user.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        // $this->setTokenObjectInSession($resetToken);
        $resetToken = $jwtManager->create($user);

        return $this->json(['message' => 'Password reset email sent.','resetToken' => $resetToken], Response::HTTP_OK);
    }
}
