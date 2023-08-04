<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Repository\UserRepository;
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

    #[Route('/user/update', name: 'update_app_user',methods: ['PUT'])]
    public function Update (#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager): Response
    {
        $document = new Document();
        $data = json_decode($request->getContent(), true);

        $user->setNom($data['nom']);
        $user->setEmail($data['email']);
        $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        $user->setTelephone($data['telephone']);
        $user->setPhoto($data['photo_user']);
        $user->setUpdateAt(new \DateTimeImmutable());

        $document->setNumDoc($data['num_doc']);
        $document->setNom($data['nom_doc']);
        $document->setPhoto($data['photo']);
        $document->setUtilisateur($user);

        if (isset($data['roles']) && is_array($data['roles'])){
            $user->setRoles($data['roles']);
        }
        $roles[] = 'ROLE_AGENCE';
        if($user->getRoles()==$roles){
            $user->addDocument($document);
            $entityManager->persist($document);
        }
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
    }


    #[Route('/user/update_photo', name: 'app_update_photo', methods: ['PUT'])]
    public function UpdatePhoto(#[CurrentUser] User $user,Request $request,FileUploader $fileUploader): Response
    {
        // $data = json_decode($request->getContent(), true);
        $image = $request->request->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $user->setPhoto($imageFileName);
        }

        return $this->json(['message' => 'Photo mis à jour avec succès'], Response::HTTP_OK);
    }

    #[Route('/user/agence/get', name: 'app_get_agence',methods: ['GET'])]
    public function getUserAgence(UserRepository $userRepository,Request $request): Response
    {
        $user = $userRepository->findUsersOfRoles(['ROLE_AGENCE']);
        $response = new Response( json_encode( array( 'agences' => $user ) ) );
        return $response;
    }
}
