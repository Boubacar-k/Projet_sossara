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
use App\Entity\Commentaire;
use App\Repository\BienImmoRepository;
use App\Repository\CommentaireRepository;
use App\Entity\User;
use App\Entity\BienImmo;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;


#[Route('/api', name: 'api_')]
class CommentaireController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['id','contenu','bien_immo'];
    public function __construct(EntityManagerInterface $entityManager,UserRepository $userRepository,PublisherInterface $publisher,
    BienImmoRepository $bienImmoRepository,CommentaireRepository $commentaireRepository){
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bienImmoRepository = $bienImmoRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->publisher = $publisher;
    }
    #[Route('/commentaire/{id}', name: 'app_commentaire',methods: ['POST'])]
    public function index(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,Request $request,SerializerInterface $serializer,int $id): Response
    {
        $data = json_decode($request->getContent(), true);

        $bienImmo = $bienImmoRepository->find($id);

        $contenu = $data['contenu'];
        $commentaire = new Commentaire();

        $commentaire->setContenu($contenu);
        $commentaire->setUtilisateur($user);

        $commentaire->setBienImmo($bienImmo);

        $this->entityManager->getConnection()->beginTransaction();
        try {

            $this->entityManager->persist($commentaire);
            $this->entityManager->flush();
            $this->entityManager->commit();
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $commentaireSerialized = $serializer->serialize($commentaire,'json', [
            'attributes' => ['id','contenu']
        ]);

        $update = new Update(
            [
                sprintf("/api/commentaire/%s",$commentaire->getId()),
                sprintf("/api/commentaire/%s",$bienImmo->getUtilisateur()->getEmail())
            ],
            $commentaireSerialized,
            true
        );

        $this->publisher->__invoke($update);

        return $this->json($commentaire,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }

    #[Route('/commentaire/get/{id}', name: 'getCommentaire')]
    public function getCom(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,CommentaireRepository $commentaireRepository,Request $request,int $id){
        // $bienImmo = $bienImmoRepository->find($id);
        $bienImmo = $bienImmoRepository->find($id);

        $bienImmoId = $bienImmo->getId();
        $commentaire = $this->commentaireRepository->findBy(['bien_immo'=>$bienImmoId]);

        // $bienImmoId = $bienImmo->getId();
        // $commentaire = $this->commentaireRepository->findCommentaireByBienByUser($bienImmoId,$user->getId());

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure',$hubUrl));
        return $this->json($commentaire,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
