<?php

namespace App\Controller;

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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;
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

            return $this->json(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur de modification",
        ]);

    }


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

            return $this->json(['message' => 'Votre photo a ete mis à jour avec succès'], Response::HTTP_OK);
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

    #[Route('/user/agence/get', name: 'app_get_agence',methods: ['GET'])]
    public function getUserAgence(UserRepository $userRepository,Request $request): Response
    {
        $user = $userRepository->findUsersOfRoles(['ROLE_AGENCE']);
        $response = new Response( json_encode( array( 'agences' => $user ) ) );
        return $response;
    }
}
