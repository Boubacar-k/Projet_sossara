<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProblemeRepository;
use App\Repository\TypeProblemeRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Repository\BienImmoRepository;
use App\Entity\User;
use App\Entity\Probleme;
use App\Entity\Reparation;

#[Route('/api', name: 'api_')]
class ReparationController extends AbstractController
{
    #[Route('/reparation/{id}', name: 'app_reparation',methods: ['POST'])]
    public function index(#[CurrentUser] User $user, Request $request,BienImmoRepository $bienImmoRepository,EntityManagerInterface $entityManager,
    TypeProblemeRepository $typeProblemeRepository,ProblemeRepository $problemeRepository,int $id): Response
    {
        $reparation = new Reparation();
        $data = json_decode($request->getContent(), true);
        $typeId = $data['type'];
        $type = $typeProblemeRepository->find($typeId);
        $biens = $bienImmoRepository->findOneBy(['id' => $id, 'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $bienUser = $biens->getUtilisateur();

        $probleme = $problemeRepository->findOneBy(['bien'=>$biens->getId(),'typeProbleme' => $type]);

        if($bienUser->getEmail() == $user->getEmail()){
            $reparation->setBien($biens);
            $reparation->setSomme($data['somme']);
            $reparation->setType($type);
            $probleme->setIsOk(true);

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
}
