<?php

namespace App\Controller;

use App\Entity\Adresse;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\BienImmoRepository;
use App\Repository\TransactionRepository;
use App\Entity\User;
use App\Entity\BienImmo;
use App\Entity\Transaction;
use App\Entity\PhotoDocument;
use App\Repository\UserRepository;
use App\Repository\DocumentRepository;
use App\Repository\PhotoDocumentRepository;
use App\Entity\Document;
use App\Entity\UserAdresse;
use App\Repository\RoleRepository;
use App\Repository\UserAdresseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;

#[Route('/api', name: 'api_')]
class HomeController extends AbstractController
{
    #[Route('/user/{id}', name: 'app_user',methods: ['GET'])]
    public function index(ManagerRegistry $doctrine,int $id): Response
    {
        $users = $doctrine->getrepository(User::class)->find($id);
        $response = new Response( json_encode( array( 'Utilisateurs' => $users ) ) );
        return $response;
    }

    #[Route('/user/child/create', name: 'app_child_user',methods: ['POST'])]
    #[AttributeIsGranted('ROLE_AGENCE')]
    public function test(#[CurrentUser] User $user,Request $request,UserPasswordHasherInterface $userPasswordHasher,MailerInterface $mailer,
    EntityManagerInterface $entityManager,UrlGeneratorInterface $urlGeneratorInterface,RoleRepository $roleRepository): Response
    {
        $agent = new User();
        $adresse = new UserAdresse();

        $roles = $roleRepository->find(4);
        $alphanum = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphanum) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphanum[$n];
        }
        $password = implode($pass);

        // $password =  'pass_'.str_replace(' ','_',$user->getNom()).'_123'; 

        $agent->setNom($request->request->get('nom'));
        $agent->setEmail($request->request->get('email'));
        $agent->addRole($roles);
        $agent->setPassword($userPasswordHasher->hashPassword($agent,$password));
        $agent->setTelephone($request->request->get('telephone'));
        if($user->isIsCertified() == true)
        {
            $agent->setIsCertified(true);
        }else{
            $agent->setIsCertified(false);
        }
        $agent->setIsVerified(true);
        $agent->setCreatedAt(new \DateTimeImmutable());
        $agent->setUpdateAt(new \DateTimeImmutable());

        $adresse->setQuartier($request->request->get('quartier'));
        $agent->addUserAdress($adresse);
        $user->addChild($agent);
        if ($request->getMethod() == Request::METHOD_POST)
        {
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($agent);
                $entityManager->persist($adresse);
                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            $confirmationUrl = $this->generateUrl('api_app_angular_redirect', [], $urlGeneratorInterface::ABSOLUTE_URL);
            $userEmail = $agent->getEmail();
            $endEmail = (new TemplatedEmail())
                ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
                ->to($agent->getEmail())
                ->subject('informations sur votre compte')
                ->htmlTemplate('admin/agent_compte.html.twig')
                ->context([
                    'userEmail' => $userEmail,
                    'password' => $password,
                    'confirmationUrl' => $confirmationUrl
                ]);;

                $mailer->send($endEmail);
            return $this->json(['message' => 'Agent ajouter avec succès','Email'=>$agent->getEmail(),'Mot_de_passe'=>$password]);
        }

        return $this->json(['message' => 'Il y a une erreur'],Response::HTTP_BAD_REQUEST);
    }

    // SUPPRIMER UN AGENT
    #[Route('/user/child/delete/{id}', name: 'app_delete_agent',methods: ['POST'])]
    #[AttributeIsGranted('ROLE_AGENCE')]
    public function Delete (#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,int $id,
    BienImmoRepository $bienImmoRepository,UserAdresseRepository $userAdresseRepository): Response
    {
        // $bien = $bienImmoRepository->find($id);

        $agent = $userRepository->findOneBy(['id' => $id,'parent' => $user ]);
        $adress = $userAdresseRepository->findOneBy(['utilisateur' => $agent->getId()]);
        $bienImmo = $bienImmoRepository->findBy(['utilisateur' => $agent]);

        foreach($bienImmo as $bien){
            $bien->setUtilisateur($user);
        }
        $entityManager->remove($adress);
        $entityManager->remove($agent);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // AFFICHER LA LISTE DES AGENT
    #[Route('/user/child/get', name: 'app_get_agent',methods: ['GET'])]
    #[AttributeIsGranted('ROLE_AGENCE')]
    public function getAgent (#[CurrentUser] User $user,UserRepository $userRepository): Response
    {

        
        $agent = $userRepository->findBy(['parent'=>$user->getId()]);

        $agents = [];
        foreach($agent as $agt){
            $agents[] = [
                'id' => $agt->getId(),
                'username' => $agt->getnom(),
                'email' => $agt->getEmail(),
                'date_de_naissance' => $agt->getDateNaissance(),
                'telephone' => $agt->getTelephone(),
                'role' => $agt->getRoles(),
                'photo' => $agt->getPhoto(),
                'agence' => $agt->getParent(),
                'adresse' => $agt->getUserAdresses() 
            ];
        }
        $response = new Response( json_encode( array( 'agents' => $agents ) ) );
        return $response;
    }

    // AFFICHER LA LISTE DES AGENT EN FONCTION DE L'ID DE L'AGENCE
    #[Route('/user/child/get/{id}', name: 'app_get_agent_by_agence',methods: ['GET'])]
    public function getAgentByAgence (UserRepository $userRepository,int $id): Response
    {

        $agent = $userRepository->findBy(['parent'=>$id]);

        $agents = [];
        foreach($agent as $agt){
            $agents[] = [
                'id' => $agt->getId(),
                'username' => $agt->getnom(),
                'email' => $agt->getEmail(),
                'date_de_naissance' => $agt->getDateNaissance(),
                'telephone' => $agt->getTelephone(),
                'role' => $agt->getRoles(),
                'photo' => $agt->getPhoto(),
                'agence' => $agt->getParent(),
                'adresse' => $agt->getUserAdresses() 
            ];
        }
        

        $response = new Response( json_encode( array( 'agents' => $agents ) ) );
        return $response;
    }

    // MODIFICATION DES INFORMATION DE L'UTILISATEUR
    #[Route('/user/update', name: 'update_app_user',methods: ['POST'])]
    public function Update (#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager): Response
    {
        $user->setNom($request->request->get('nom'));
        $user->setEmail($request->request->get('email'));
        $user->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        $user->setTelephone($request->request->get('telephone'));

        $user->setUpdateAt(new \DateTimeImmutable());


        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            $userInfo = [
                'id' => $user->getId(),
                'username' => $user->getnom(),
                'email' => $user->getEmail(),
                'date_de_naissance' => $user->getDateNaissance(),
                'telephone' => $user->getTelephone(),
            ];
            return $this->json(['message' => 'Utilisateur mis à jour avec succès','user'=> $userInfo], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur de modification",
        ]);

    }

    // MODIFIER LA PHOTO DE L'UTILISATEUR
    #[Route('/user/update/photo', name: 'app_user_update_photo', methods: ['POST'])]
    public function UpdatePhoto(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager,FileUploader $fileUploader): Response
    {
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $user->setPhoto($imageFileName);
            $user->setUpdateAt(new \DateTimeImmutable());
        }

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            $userInfo = [
                'id' => $user->getId(),
                'photo' => $user->getPhoto(),
            ];

            return $this->json(['message' => 'Votre photo a ete mis à jour avec succès','photo' => $userInfo], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de modification",
        ]);
    }

    // #[Route('/user/photo/get', name: 'app_user_get_photo', methods: ['GET'])]
    // public function getPhoto(#[CurrentUser] User $user,FileUploader $fileUploader): Response
    // {
    //     $photo = $user->getPhoto();
    //     return $this->json(['photo' => $photo], Response::HTTP_OK);
    // }

    // MODIFIER LE MOT DE PASSE DE L'UTILISATEUR
    #[Route('/user/password_reset', name: 'password_reset_app_user',methods: ['POST'])]
    public function reset (#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager,UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $data = json_decode($request->getContent(), true);

        $oldPass = $data['old_password'];
        if (!$userPasswordHasher->isPasswordValid($user, $oldPass)) {
            return $this->json(['etat' => false, 'message' => 'Mot de passe incorrecte'], Response::HTTP_UNAUTHORIZED);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user,$data['password']));
            $user->setUpdateAt(new \DateTimeImmutable());
            if ($request->getMethod() == Request::METHOD_POST){
                $entityManager->getConnection()->beginTransaction();
                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $entityManager->commit();
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    throw $e;
                }
    
                return $this->json(['message' => 'mot de passe mis à jour avec succès'], Response::HTTP_OK);
            }

        return $this->json([
            'erreur' => "erreur de mise a jour",
        ]);

    }

    // RETOURNER LES LOCATAIRES DE L'UTILISATEUR
    #[Route('/user/bien/immo/rent/get', name: 'app_user_bien_immo_get_rent', methods: ['GET'])]
    public function getRentUser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $locataire= [];

        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        foreach ($bienImmo as $bien) {
            $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
            // $locataire[] = $bien;
            foreach($transactions as $transaction){
                $locataire[] = $transaction->getUtilisateur();
            }
        }
        $response = new Response( json_encode( array( 'locataire' => $locataire) ) );
        return $response;
    }

    // RETOURNER LES LOCATAIRES DE L'UTILISATEUR (AGENCE)
    #[Route('/user/agence/tenant/get', name: 'app_user_bien_immo_agence_get_rent', methods: ['GET'])]
    #[AttributeIsGranted('ROLE_AGENCE')]
    public function getRentAgenceUser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository,
    UserRepository $userRepository): Response
    {
        $locataire= [];

        $agent = $userRepository->findBy(['parent'=>$user->getId()]);
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        foreach ($bienImmo as $bien) {
            $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
            foreach($transactions as $transaction){
                $locataire[] = $transaction->getUtilisateur();
            }
        }

        $agentLocataire= [];
        foreach ($agent as $agt){
            $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$agt->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
            foreach ($bienImmo as $bien) {
                $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
                foreach($transactions as $transaction){
                    $agentLocataire[] = $transaction->getUtilisateur();
                }
            }
        }
        
        $response = new Response( json_encode( array( 'locataires' => $locataire,'locataires_agent' => $agentLocataire) ) );
        return $response;
    }

    // RETOURNER LES ACHETEURS DE L'UTILISATEUR
    #[Route('/user/bien/immo/get/sell', name: 'app_user_bien_get_sell', methods: ['GET'])]
    public function getSellUser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $locataire= [];

        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        foreach ($bienImmo as $bien) {
            $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
            // $locataire[] = $bien;
            foreach($transactions as $transaction){
                $locataire[] = $transaction->getUtilisateur();
            }
        }
        $response = new Response( json_encode( array( 'locataire' => $locataire) ) );
        return $response;
    }

    // RETOURNER LES ACHETEURS DE L'UTILISATEUR (AGENCE)
    #[Route('/user/agence/buyer/get', name: 'app_user_bien_agence_get_sell', methods: ['GET'])]
    #[AttributeIsGranted('ROLE_AGENCE')]
    public function getSellAgenceUser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository,
    UserRepository $userRepository): Response
    {
        $locataire= [];

        $agent = $userRepository->findBy(['parent'=>$user->getId()]);
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        foreach ($bienImmo as $bien) {
            $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
            // $locataire[] = $bien;
            foreach($transactions as $transaction){
                $locataire[] = $transaction->getUtilisateur();
            }
        }

        $agentAcheteur= [];
        foreach ($agent as $agt){
            $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$agt->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => true]);
            foreach ($bienImmo as $bien) {
                $transactions = $transactionRepository->findBy(['bien' => $bien->getId()]);
                foreach($transactions as $transaction){
                    $agentAcheteur[] = $transaction->getUtilisateur();
                }
            }
        }
        $response = new Response( json_encode( array( 'locataire' => $locataire,'agent_acheteurs' => $agentAcheteur) ) );
        return $response;
    }


    // RETOURNER LA LISTE DES AGENCES
    #[Route('/user/agence/get', name: 'app_get_agence',methods: ['GET'])]
    public function getUserAgence(UserRepository $userRepository,Request $request): Response
    {
        $user = $userRepository->findUsersOfRoles(['ROLE_AGENCE']);
        $response = new Response( json_encode( array( 'agences' => $user ) ) );
        return $response;
    }
}
