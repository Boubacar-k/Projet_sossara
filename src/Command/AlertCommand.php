<?php

namespace App\Command;

use App\Repository\BienImmoRepository;
use App\Repository\TransactionRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-scheduled-email',
    description: 'Send an email based on a schedule'
)]
class AlertCommand extends Command
{
    private $mailer;
    private $transactionRepository;
    private $bienImmoRepository;

    public function __construct(MailerInterface $mailer, TransactionRepository $transactionRepository, BienImmoRepository $bienImmoRepository)
    {
        parent::__construct();
        $this->mailer = $mailer;
        $this->transactionRepository = $transactionRepository; // Fix missing assignment operator (=)
        $this->bienImmoRepository = $bienImmoRepository; // Fix missing assignment operator (=)
    }

    protected function configure()
    {
        $this
            ->setName('app:send-scheduled-email')
            ->setDescription('Send an email based on a schedule');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bienImmo = $this->bienImmoRepository->findBy(['deletedAt' => null, 'is_rent' => true, 'is_sell' => false]);
        foreach ($bienImmo as $bien) {
            $transaction = $this->transactionRepository->findBy(['bien' => $bien->getId()]);

            foreach ($transaction as $transac) {
                $user = $transac->getUtilisateur();
                $date = $transac->getFiniAt();

                $today = new \DateTimeImmutable();
                $scheduledDate = new \DateTimeImmutable(strval($date));

                if ($today->format('Y-m-d') === $scheduledDate->format('Y-m-d')) {
                    $email = (new TemplatedEmail())
                        ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
                        ->to($user->getEmail())
                        ->subject('Scheduled Email')
                        ->htmlTemplate('admin/index.html.twig') // Remplacez par le chemin de votre modèle
                        ->context([
                            // Les données que vous souhaitez passer au modèle Twig
                            'user' => $user,
                            // ...
                        ]);

                    $this->mailer->send($email);
                } else {
                    $output->writeln('No email scheduled for today.');
                }
            }
        }

        return Command::SUCCESS;
    }
}