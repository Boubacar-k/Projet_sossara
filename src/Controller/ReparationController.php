<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProblemeRepository;
use App\Repository\ReparationRepository;
use App\Repository\TypeProblemeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Repository\BienImmoRepository;
use App\Entity\User;
use App\Entity\Reparation;

#[Route('/api', name: 'api_')]
class ReparationController extends AbstractController
{
    #[Route('/reparation/{id}', name: 'app_reparation',methods: ['POST'])]
    public function index(#[CurrentUser] User $user, Request $request,BienImmoRepository $bienImmoRepository,EntityManagerInterface $entityManager,
    ProblemeRepository $problemeRepository,int $id): Response
    {
        $reparation = new Reparation();
        $data = json_decode($request->getContent(), true);
        $biens = $bienImmoRepository->findOneBy(['utilisateur' => $user->getId(),'id' => $id, 'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $bienUser = $biens->getUtilisateur();

        $probleme = $problemeRepository->findOneBy(['bien'=>$biens->getId(),'is_ok' => false]);

        if($bienUser->getEmail() == $user->getEmail()){
            $reparation->setBien($biens);
            $reparation->setSomme($data['somme']);
            $reparation->setType($probleme->getTypeProbleme());
            $reparation->setProbleme($probleme);
            $probleme->setIsOk(true);
            $probleme->setUpdatedAt(new \DateTimeImmutable());

            if ($request->getMethod() == Request::METHOD_POST){
                $entityManager->getConnection()->beginTransaction();
                try {
    
                    $entityManager->persist($reparation);
                    $entityManager->flush();
                    $entityManager->commit();
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    throw $e;
                }
                return $this->json([
                    'message' => 'reparation enregistee avec succes',
                ]);
            }
            return $this->json([
                'message' => 'Quelque chose s\'est mal passee',
            ]);
        }

        return $this->json([
            'message' => 'Ce bien ne vous appartient pas',
        ]);
    }

    #[Route('/reparation/list/get', name: 'getReparation',methods: ['GET'])]
    public function getMyReparation(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,ReparationRepository $reparationRepository){

        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);

        $reparations= [];

            foreach ($bienImmo as $bien) {
                $reparationList = $reparationRepository->findBy(['bien'=>$bien->getId(),'is_ok' => false]);
                foreach ($reparationList as $reparation) {
                    $reparations[] = $reparation;
                }
            }

        $response = new Response( json_encode( array( 'attributes' => $reparations) ) );
        return $response;
    }

    #[Route('/reparation/confirm/list', name: 'getReparationConfirm',methods: ['GET'])]
    public function confirmerReparation(#[CurrentUser] User $user,ReparationRepository $reparationRepository,ProblemeRepository $problemeRepository,){

        $probleme = $problemeRepository->findBy(['utilisateur'=>$user->getId(),'is_ok' => true]);

        $reparations= [];

            foreach ($probleme as $blem) {
                $reparationList = $reparationRepository->findBy(['probleme'=>$blem->getId(),'is_ok' => false]);
                foreach ($reparationList as $reparation) {
                    $reparations[] = $reparation;
                }
            }

        $response = new Response( json_encode( array( 'reparations' => $reparations) ) );
        return $response;
    }


    #[Route('/reparation/confirmer/list/{id}', name: 'confirmer_reparation',methods: ['POST'])]
    public function comfirm(#[CurrentUser] User $user,EntityManagerInterface $entityManager,ProblemeRepository $problemeRepository,
    ReparationRepository $reparationRepository,int $id): Response
    {
        $probleme = $problemeRepository->findBy(['utilisateur'=>$user->getId(),'is_ok' => true]);
        foreach($probleme as $blem){
            $reparation = $reparationRepository->findOneBy(['probleme' =>$blem->getId(),'id'=>$id,'is_ok' => false]);
            $reparation->setIsOk(true);
            $reparation->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($reparation);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            return $this->json([
                'message' => 'reparation enregistee avec succes',
            ]);
        }
    }


}
