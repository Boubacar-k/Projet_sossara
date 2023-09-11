<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BienImmoRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/admin', name: 'api_admin')]
#[AttributeIsGranted('ROLE_SUPER_ADMIN')]
class AdminController extends AbstractController
{
    // SUPPRIMER UN UTILISATEUR
    #[Route('/user', name: 'app_admin')]
    public function index(#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,
    BienImmoRepository $bienImmoRepository,int $id,TransactionRepository $transactionRepository): Response
    {
        $deleteUser = $userRepository->find($id);
        $bienImmo = $bienImmoRepository->findBy(['utilisateur' => $deleteUser]);

        foreach($bienImmo as $bien){
            $bien->setUtilisateur($user);
            $bien->setDeletedAt(new \DateTimeImmutable());
            $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
            foreach($transaction as $transac){
                $transac->setIsDeleted(true);
            }
        }
        $entityManager->remove($deleteUser);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // SUPPRIMER UN AGENT
    #[Route('/user/child/delete/{id}', name: 'app_delete_agent',methods: ['POST'])]
    public function DeleteAgent (#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,int $id,
    BienImmoRepository $bienImmoRepository): Response
    {
        $agent = $userRepository->find($id);

        $agence = $agent->getParent();
        $bienImmo = $bienImmoRepository->findBy(['utilisateur' => $agent]);

        foreach($bienImmo as $bien){
            $bien->setUtilisateur($agence);
        }
        $entityManager->remove($agent);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // MODIFIER LE STATUS D'UN UTILISATEUR
    #[Route('/user/setCertify/{id}', name: 'app_setCertication_agent',methods: ['POST'])]
    public function userCertification (#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,int $id): Response
    {
        $agent = $userRepository->find($id);

        
        if($agent->isIsCertified() == false){
            $agent->setIsCertified(true);
        }else{
            $agent->setIsCertified(false);
        }
        $entityManager->getConnection()->beginTransaction();
        try {

            $entityManager->persist($agent);
            $entityManager->flush();
            $entityManager->commit();
            
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
        return $this->json(['message' => 'Modification effectuee avec succès'], Response::HTTP_OK);
    }

    // SUPPRIMER UN BIEN
    #[Route('/bien/immo/delete/{id}', name: 'app_delete_bienImmo',methods: ['POST'])]
    public function Delete (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,int $id,
    TransactionRepository $transactionRepository): Response
    {
        $bien = $bienImmoRepository->findOneBy(['id' => $id,'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);

        $bien->setUtilisateur($user);
            $bien->setDeletedAt(new \DateTimeImmutable());
            $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
            foreach($transaction as $transac){
                $transac->setIsDeleted(true);
            }
        $entityManager->persist($bien);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // LISTE DE TOUS LES AGENTS
    #[Route('/user/agent/get', name: 'app_all_agent_get',methods: ['GET'])]
    public function getAgent (#[CurrentUser] User $user,UserRepository $userRepository): Response
    {

        $agent = $userRepository->findUsersWithParent();
        $response = new Response( json_encode( array( 'agents' => $agent ) ) );
        return $response;
    }
}
