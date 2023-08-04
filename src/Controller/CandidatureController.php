<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Repository\BienImmoRepository;
use App\Repository\CandidatureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use App\Entity\BienImmo;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class CandidatureController extends AbstractController
{
    #[Route('/candidature/{id}', name: 'app_candidature',methods: ['POST'])]
    public function index(#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,int $id): Response
    {
        $candidature = new Candidature();
        $bien = $bienImmoRepository->find($id);
        
        $bienUser = $bien->getUtilisateur();

        $candidature->setUtilisateur($user);
        $candidature->setBien($bien);
        if($bienUser->getEmail() == $user->getEmail()){
            throw new \Exception("Vous ne pouvez pas envoyer de candidature pour ce bien");
        }
        $entityManager->getConnection()->beginTransaction();
        try {

            $entityManager->persist($candidature);
            $entityManager->flush();
            $entityManager->commit();
            
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
        return $this->json(['message' => 'Candidature envoye avec succès'], Response::HTTP_OK);
    }

    #[Route('/candidature/get', name: 'app_candidature_get',methods: ['GET'])]
    public function getCandidature(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,CandidatureRepository $candidatureRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId()]);
        $candidatures= [];

            foreach ($bienImmo as $bien) {
                $candidaturesList = $candidatureRepository->findBy(['bien'=>$bien->getId()]);
                foreach ($candidaturesList as $candidature) {
                    $candidatures[] = $candidature;
                }
            }
        // $candidature = $candidatureRepository->findBy(['bien'=>$bienImmo->getId()]);
        
        $response = new Response( json_encode( array( 'candidature' => $candidatures ) ) );
        return $response;
    }
}
