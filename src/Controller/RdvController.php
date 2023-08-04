<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\PublisherInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Rdv;
use App\Repository\BienImmoRepository;
use App\Repository\RdvRepository;
use App\Entity\User;
use App\Entity\BienImmo;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;

#[Route('/api', name: 'api_')]
class RdvController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['id','heure','date','bien_immo'];
    public function __construct(EntityManagerInterface $entityManager,UserRepository $userRepository,PublisherInterface $publisher,
    BienImmoRepository $bienImmoRepository,RdvRepository $rdvRepository){
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bienImmoRepository = $bienImmoRepository;
        $this->RdvRepository = $rdvRepository;
        $this->publisher = $publisher;
    }
    #[Route('/rdv/{id}', name: 'app_rdv',methods: ['POST'])]
    public function index(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,Request $request,SerializerInterface $serializer,int $id): Response
    {
        $data = json_decode($request->getContent(), true);

        $bienImmo = $bienImmoRepository->find($id);

        $bienUser = $bienImmo->getUtilisateur();

        if($bienUser->getEmail() == $user->getEmail()){
            throw new \Exception("Vous ne pouvez vous envoyer pas un rendez-vous");
        }
        $heure = $data['heure'];
        $date = $data['date'];
        $rdv = new Rdv();

        $rdv->setHeure($heure);
        $rdv->setDate(new \DateTime($date));
        $rdv->setUtilisateur($user);

        $rdv->setBien($bienImmo);

        $this->entityManager->getConnection()->beginTransaction();
        try {

            $this->entityManager->persist($rdv);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $rdvSerialized = $serializer->serialize($rdv,'json', [
            'attributes' => ['id','heure','date']
        ]);

        $update = new Update(
            [
                sprintf("/api/rdv/%s",$rdv->getId()),
                sprintf("/api/rdv/%s",$bienImmo->getUtilisateur()->getEmail())
            ],
            $rdvSerialized,
            true
        );

        $this->publisher->__invoke($update);

        return $this->json($rdv,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    #[Route('/rdv/get/mine', name: 'getMineRdv',methods: ['GET'])]
    public function getCom(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,RdvRepository $rdvRepository,Request $request){

        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId()]);

        $Rdv= [];

            foreach ($bienImmo as $bien) {
                $bienImmoId = $bien->getId();
                $rdv = $rdvRepository->findBy(['bien'=>$bienImmoId]);
                // $candidaturesList = $candidatureRepository->findBy(['bien'=>$bien->getId()]);
                foreach ($rdv as $rdv) {
                    $Rdv[] = $rdv;
                }
            }
        // $bienImmo = $bienImmoRepository->find($id);
        // $bienImmoId = $bienImmo->getId();
        // $rdv = $rdvRepository->findRdvByBienByUser($bienImmoId,$user->getId());

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure',$hubUrl));
        return $this->json($Rdv,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    #[Route('/rdv/get', name: 'getRdv',methods: ['GET'])]
    public function getRDV(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,RdvRepository $rdvRepository,Request $request){
        // $bienImmo = $bienImmoRepository->find($id);
        // $bienImmoId = $bienImmo->getId();
        $rdv = $rdvRepository->findBy(['id'=>$user->getId()]);
        return $this->json($rdv,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
