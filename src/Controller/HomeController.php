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
        $data = json_decode($request->getContent(), true);

        $user->setNom(trim($data['nom']));
        $user->setEmail(trim($data['email']));
        $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        $roles[] = 'ROLE_PROPRIETAIRE';
        if (isset($data['roles'])) {
            $newRoles = $data['roles'];

            $newRoles = trim($newRoles);
        
            if ($newRoles === 'PROPRIETAIRE') {
                $user->setRoles(['ROLE_PROPRIETAIRE']);
            } elseif ($newRoles === 'AGENCE') {
                $user->setRoles(['ROLE_AGENCE']);
            } else {
                // Rôle invalide
                return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
            }
        }
        $user->setTelephone(trim($data['telephone']));
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

            return $this->json(['message' => 'Photo mis à jour avec succès'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de modification",
        ]);
    }

    #[Route('/user/photo/get', name: 'app_user_get_photo', methods: ['GET'])]
    public function getPhoto(#[CurrentUser] User $user,FileUploader $fileUploader): Response
    {
        $photo = $user->getPhoto();
        return $this->json(['photo' => $photo], Response::HTTP_OK);
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
