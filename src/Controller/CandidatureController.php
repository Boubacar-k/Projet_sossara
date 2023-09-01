<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Repository\BienImmoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Transaction;
use App\Entity\TypeTransaction;
use App\Repository\TypeTransactionRepository;
use App\Repository\CandidatureRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class CandidatureController extends AbstractController
{
    #[Route('/candidature/{id}', name: 'app_candidature',methods: ['POST'])]
    public function index(#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    CandidatureRepository $candidatureRepository,int $id): Response
    {
        $candidature = new Candidature();
        $bien = $bienImmoRepository->find($id);
        $candidaturesList = $candidatureRepository->findOneBy(['bien'=>$bien->getId(),'utilisateur' => $user->getId()]);
        $bienUser = $bien->getUtilisateur();

        if($candidaturesList){
            throw new \Exception("Vous avez deja envoye votre candidature pour ce bien");
        }

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
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $candidatures= [];

            foreach ($bienImmo as $bien) {
                $candidaturesList = $candidatureRepository->findBy(['bien'=>$bien->getId(),'is_accepted' => false,'is_cancel' => false]);
                foreach ($candidaturesList as $candidature) {
                    $candidatures[] = $candidature;
                }
            }
        // $candidature = $candidatureRepository->findBy(['bien'=>$bienImmo->getId()]);
        
        $response = new Response( json_encode( array( 'candidature' => $candidatures ) ) );
        return $response;
    }

    #[Route('/candidature/accept/{id}', name: 'app_candidature_accept',methods: ['POST'])]
    public function accept(#[CurrentUser] User $user,EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    TypeTransactionRepository $typeTransactionRepository,CandidatureRepository $candidatureRepository,int $id): Response
    {
        // $candidature = new Candidature();
        $transaction = new Transaction();
        $candidature = $candidatureRepository->find($id);
        $bien = $candidature->getBien();
        $candidatureUser = $candidature->getUtilisateur();
        $typeAchat = $typeTransactionRepository->find(1);
        $typeLocation = $typeTransactionRepository->find(2);
        $num = $candidatureUser->getTelephone();
        
        $bienUser = $bien->getUtilisateur();
        $bienStatut = $bien->getStatut();
        $bienPeriode = $bien->getPeriode();
        $periodeId = $bienPeriode->getId();
        $bienSomme = $bien->getPrix();
        if($bienUser->getEmail() == $user->getEmail()){
            $candidature->setIsAccepted(true);
            if($bienStatut == "A louer"){
                $transaction->setTypeTransaction($typeLocation);
                $bien->setIsRent(true);
                if($periodeId == 1){
                    $date = new \DateTimeImmutable();
                    $transaction->setFiniAt($date->add(new \DateInterval('P1H')));
                }
                if($periodeId == 2){
                    $date = new \DateTimeImmutable();
                    $transaction->setFiniAt($date->add(new \DateInterval('P1D')));
                }
                if($periodeId == 3){
                    $date = new \DateTimeImmutable();
                    $transaction->setFiniAt($date->add(new \DateInterval('P1W')));
                }
                if($periodeId == 4){
                    $date = new \DateTimeImmutable();
                    $transaction->setFiniAt($date->add(new \DateInterval('P1M')));
                }
                if($periodeId == 5){
                    $date = new \DateTimeImmutable();
                    $transaction->setFiniAt($date->add(new \DateInterval('P1Y')));
                }
                $transaction->setBien($bien);
                $transaction->setUtilisateur($candidatureUser);
                $transaction->setStatut("En location");
                $transaction->setSomme($bienSomme);
                $transaction->setCreatedAt(new \DateTimeImmutable());
                $transaction->setUpdateAt(new \DateTimeImmutable());
            }
            elseif($bienStatut == "A vendre"){
                $transaction->setTypeTransaction($typeAchat);
                $bien->setIsSell(true);
                $transaction->setBien($bien);
                $transaction->setUtilisateur($candidatureUser);
                $transaction->setStatut('Vendu');
                $transaction->setSomme($bienSomme);
                $transaction->setCreatedAt(new \DateTimeImmutable());
                $transaction->setUpdateAt(new \DateTimeImmutable());
            }else{
                throw new \Exception("Attention ce bien n'a pas de statut");
            }
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($candidature);
                $entityManager->persist($transaction);
                $entityManager->flush();
                $entityManager->commit();
                
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            return $this->json(['message' => 'Candidature accepte'], Response::HTTP_OK);
        }

        return $this->json(['erreur' => 'ce bien ne vous appartient pas']);
    }


    #[Route('/candidature/refuse/{id}', name: 'app_candidature_refuse',methods: ['POST'])]
    public function refuse(#[CurrentUser] User $user,EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    CandidatureRepository $candidatureRepository,int $id): Response
    {
        $candidature = $candidatureRepository->find($id);
        $bien = $candidature->getBien();
        
        $bienUser = $bien->getUtilisateur();
        if($bienUser->getEmail() == $user->getEmail()){
            $candidature->setIsCancel(true);
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($candidature);
                $entityManager->flush();
                $entityManager->commit();
                
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            return $this->json(['message' => 'Candidature refusee'], Response::HTTP_OK);
        }

        return $this->json(['erreur' => 'ce bien ne vous appartient pas']);
    }
}
