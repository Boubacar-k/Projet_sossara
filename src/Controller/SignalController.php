<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\TransactionRepository;
use App\Repository\BienImmoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ProblemeRepository;
use App\Repository\TypeProblemeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\WebLink\Link;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\User;
use App\Entity\PhotoReclamation;
use App\Entity\Probleme;
use App\Service\FileUploader;

#[Route('/api', name: 'api_')]
class SignalController extends AbstractController
{
    private $publisher;
    const ATTRIBUTES_TO_SERIALIZE = ['id','contenu','bien','typeProbleme','createdAt','photoReclamations','utilisateur'=>['nom','email','telephone','photo']];
    public function __construct(PublisherInterface $publisher){
        $this->publisher = $publisher;
    }
    #[Route('/signal/{id}', name: 'app_signal',methods: ['POST'])]
    public function index(#[CurrentUser] User $user, Request $request,EntityManagerInterface $entityManager,TransactionRepository $transactionRepository,
    BienImmoRepository $bienImmoRepository,TypeProblemeRepository $typeProblemeRepository,int $id,SerializerInterface $serializer
    ,FileUploader $fileUploader): Response
    {
        $probleme = new Probleme();
        $data = json_decode($request->getContent(), true);
        $typeId = $data['type'];
        $type = $typeProblemeRepository->find($typeId);
        $biens = $bienImmoRepository->findOneBy(['id' => $id, 'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $transac = $transactionRepository->findOneBy(['utilisateur'=>$user->getId(),'bien' => $biens->getId()]);
        if($transac == null){
            throw new \Exception("Vous n'etes pas le locataire de ce bien");
        }

        $bien = $transac->getBien();
        $bienUser = $biens->getUtilisateur();

        if($bienUser->getEmail() == $user->getEmail()){
            throw new \Exception("Vous ne pouvez pas faire cette action");
        }

        $probleme->setUtilisateur($user);
        $probleme->setBien($bien);
        $probleme->setTypeProbleme($type);
        $probleme->setContenu(trim($data['contenu']));

        $images = $request->files->get('photo');
        if ($images!=null) {
            foreach ($images as $image){
                $imageFileName = $fileUploader->upload($image);
                
                $photo = new PhotoReclamation();
                $photo->setNom($imageFileName);
                $probleme->addPhotoReclamation($photo);
                $entityManager->persist($photo);
            }
        }

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($probleme);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            $problemeSerialized = $serializer->serialize($probleme,'json', [
                'attributes' => ['id','contenu','photoReclamations']
            ]);
            $update = new Update(
                [
                    sprintf("/api/signal/%s",$probleme->getId()),
                    sprintf("/api/signal/%s",$probleme->getUtilisateur()->getEmail())
                ],
                $problemeSerialized,
                true
            );
    
            $this->publisher->__invoke($update);
            return $this->json(['message' => 'Votre signal a ete envoye au proprietaire'], Response::HTTP_OK);
        }
        return $this->json([
            'message' => 'il y a une erreur',
        ]);
    }

    #[Route('/signal/get', name: 'getSignal',methods: ['GET'])]
    public function getSignalerProbleme(#[CurrentUser] User $user,Request $request,BienImmoRepository $bienImmoRepository,ProblemeRepository $problemeRepository){

        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);

        $problemes= [];

            foreach ($bienImmo as $bien) {
                $problemeList = $problemeRepository->findBy(['bien'=>$bien->getId(),'is_ok' => false]);
                foreach ($problemeList as $probleme) {
                    $problemes[] = $probleme;
                }
            }

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure',$hubUrl));
        // $response = new Response( json_encode( array( 'attributes' => $problemes) ) );
        // return $response;
        return $this->json($problemes,Response::HTTP_OK,[],[
            'attributes' => self::ATTRIBUTES_TO_SERIALIZE
        ]);
    }
}
