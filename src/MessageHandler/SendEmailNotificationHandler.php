<?php

namespace App\MessageHandler;

use App\Message\SendEmailNotification;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Mime\Email;

#[AsMessageHandler()]
class SendEmailNotificationHandler implements MessageHandlerInterface
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke(SendEmailNotification $message,UserInterface $user)
    {
        // Récupérez les informations nécessaires du message
        $userId = $message->getUserId();
        $messageContent = $message->getMessage();

        // Récupérez l'utilisateur en fonction de $userId, par exemple depuis la base de données
        // ...

        // Créez et envoyez l'e-mail
        $email = (new TemplatedEmail())
            ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
            ->to($user->getEmail())
            ->subject('Rappel de fin de location')
            ->htmlTemplate('admin/rappel.html.twig');

        $this->mailer->send($email);
    }
    
}