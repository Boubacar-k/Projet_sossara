<?php

namespace App\Controller;

use App\Entity\BienImmo;
use App\Entity\TypeImmo;
use App\Entity\PhotoImmo;
use App\Entity\User;
use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\Commodite;
use App\Entity\Commune;
use App\Entity\Adresse;
use App\Repository\BienImmoRepository;
use App\Repository\PhotoImmoRepository;
use App\Repository\TypeImmoRepository;
use App\Repository\UserRepository;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use App\Repository\CommuneRepository;
use App\Repository\AdresseRepository;
use App\Repository\CommoditeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FileUploader;

#[Route('/api', name: 'api_')]
class BienImmoController extends AbstractController
{
    #[Route('/bien/immo', name: 'app_bien_immo')]
    public function index(Request $request,BienImmoRepository $bienImmoRepository): Response
    {
        $biens = $bienImmoRepository->findBy(['deletedAt' => null]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/user/{id}', name: 'app_user_bien_immo')]
    public function findByUser(Request $request,BienImmoRepository $bienImmoRepository,int $id): Response
    {
        $biens = $bienImmoRepository->findBy(['utilisateur' => $id]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/type/immo', name: 'app_type_immo')]
    public function type(Request $request,PaysRepository $paysRepository, RegionRepository $regionRepository,
    CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,): Response
    {
        $type = $typeImmoRepository->findAll();
        $pays = $paysRepository->findAll();
        $region = $regionRepository->findAll();
        $commodite = $commoditeRepository->findAll();
        $commune = $communeRepository->findAll();
        $response = new Response(json_encode( array( 'type' => $type,'pays' => $pays,
        'region' => $region,
        'commodite' => $commodite,
        'commune' => $commune  ) ) );
        return $response;
    }

    #[Route('/bien/immo/new', name: 'app_new_immo')]
    #[IsGranted('ROLE_USER')]
    public function createBienImmo(
        #[CurrentUser] User $user,
        Request $request, EntityManagerInterface $entityManager,PaysRepository $paysRepository, RegionRepository $regionRepository,
        CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,FileUploader $fileUploader ): Response 
    {
        $pays = $paysRepository->findAll();
        $region = $regionRepository->findAll();
        // $commodite = $commoditeRepository->find(1);
        // $commodite = $entityManager->getRepository(Commodite::class)->find(1);
        // $commune = $communeRepository->find(4);
        // $type = $typeImmoRepository->find(3);


        $data = json_decode($request->getContent(), true);

        $commodite = $data['id'];
        $type = $data['typeId'];
        $commune = $data['communeId'];
        $adresse = new Adresse();
        $immo = new BienImmo();
        $photo = new PhotoImmo();
        // $immo->setNbPiece($data['nb_piece']);
        $immo->setNom($data['nom']);
        $immo->setChambre($data['chambre']);
        $immo->setCuisine($data['cuisine']);
        $immo->setToilette($data['toilette']);
        $immo->setSurface($data['surface']);
        $immo->setPrix($data['prix']);
        $immo->setStatut($data['statut']);
        $immo->setDescription($data['description']);
        $immo->setTypeImmo($type);
        $immo->setCreatedAt(new \DateTimeImmutable());
        $immo->setUpdateAt(new \DateTimeImmutable());
        $image = $request->request->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            // $photo->setNom($imageFileName);
            $immo->addPhotoImmo($imageFileName);
        }
        

        $adresse->setQuartier($data['quartier']);
        $adresse->setRue($data['rue']);
        $adresse->setPorte($data['porte']);
        $adresse->setCommune($commune);

        $immo->setAdresse($adresse);
        $immo->setUtilisateur($user);
        // $commodites->addBienImmo($immo);//
        $immo->addCommodite($commodite);

        // $formImmo = $this->createForm(BienImmoFormType::class, $immo);
        // $formImmo->handleRequest($request);

        // $editForm = $this->createForm(AdressFormType::class, $adresse);
        // $editForm->handleRequest($request);

        if ($request->getMethod() == Request::METHOD_POST) {

            try {

                $entityManager->persist($immo);
                $entityManager->persist($adresse);
                $entityManager->flush();
                $entityManager->commit();
                
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            // $commodite->addBienImmo($immo);
            return $this->json(['message' => 'Le bien a été jouté avec succès'], Response::HTTP_OK);
        }

        return $this->json(['message' => 'il y a une erreur ']);;
    }

    #[Route('/bien/immo/type/{id}', name: 'app_type_bien_immo')]
    public function show_by_type(TypeImmoRepository $typeImmoRepository,int $id): Response
    {
        $type = $typeImmoRepository->find($id);
        $biens = $type->getBienImmo();
        foreach($biens as $bien){
            $response = new Response( json_encode( array( 'biens' => $bien ) ) );
            return $response;
        }
    }

    #[Route('/bien/immo/commune/{id}', name: 'app_bien_immo_commune')]
    public function show_by_commune(BienImmoRepository $bienImmoRepository,AdresseRepository $adresseRepository,CommuneRepository $communeRepository,int $id): Response
    {
        $commune = $communeRepository->find($id);
        $communeId = $commune->getId();
        // $adresse = $adresseRepository->findByCommune($communeId);
        $adresses = $adresseRepository->findBy(['commune' => $communeId]);
        
        $biens = [];

        foreach ($adresses as $adresse) {
            $adresseId = $adresse->getId();
            $bienImmo = $bienImmoRepository->findBienByCommune($adresseId, $communeId);

            foreach ($bienImmo as $bien) {
                $biens[] = $bien;
            }
        }
        $response = new Response(json_encode(['biens' => $biens]));
        return $response;
        // foreach($adresses as $adresse){
        //     $adresseId = $adresse->getId();
        //     $bienImmo = $bienImmoRepository->findBienByCommune($adresseId,$communeId);
        //     foreach($bienImmo as $bien){
        //         $response = new Response( json_encode( array( 'biens' => $bien ) ) );
        //         return $response;
        //     }
        // }
    }

    #[Route('/bien/immo/commodite/{id}', name: 'app_commodite_bien_immo')]
    public function show_by_commodite(EntityManagerInterface $entityManager,int $id): Response
    {
        // $commodite = $entityManager->getRepository(Commodite::class)->find($id);
        // $biens = $commodite->getBienImmos();

        $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('b')
        ->innerJoin('b.commodites', 's', 'WITH', 's.id = :commoditeId')
        ->setParameter('commoditeId', $id)
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

        $response = new Response(json_encode(array('biens' => $biens)));
        return $response;
    }

    #[Route('/bien/immo/piece/{piece}', name: 'app_piece_bien_immo')]
    public function show_by_piece(BienImmoRepository $bienImmoRepository,int $piece): Response
    {
        $biens = $bienImmoRepository->findByPiece($piece);
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/{id}', name: 'app_id_bien_immo')]
    public function show_by_bienId(BienImmoRepository $bienImmoRepository,int $id): Response
    {
        $biens = $bienImmoRepository->find($id);
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/statut/{statut}', name: 'app_statut_bien_immo')]
    public function show_by_statut(EntityManagerInterface $entityManager,string $statut): Response
    {
        // $biens = $bienImmoRepository->findByStatut($statut);

        $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('o')
           ->andWhere('o.statut LIKE :statut')
           ->setParameter('statut', '%'.$statut.'%')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult();

        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/nom/{nom}', name: 'app_nom_bien_immo')]
    public function show_by_nom(EntityManagerInterface $entityManager,string $nom): Response
    {
        $type = $entityManager->getRepository(TypeImmo::class)->createQueryBuilder('o')
           ->andWhere('o.nom LIKE :nom')
           ->setParameter('nom', '%'.$nom.'%')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult();

        //    $tp = $entityManager->getRepository(TypeImmo::class)->findBy(['nom'=> $nom]);


           $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('b')
           ->andWhere('b.typeImmo = :typImmo')
           ->setParameter('typImmo', $type)
           ->setMaxResults(10)
           ->getQuery()
           ->getResult();

        // $type = $entityManager->getRepository(TypeImmo::class)->findBy(['nom'=> $nom]);
        // $biens = $type->getBienImmo();
        // dd($biens);
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
        return $this->json(['message'=>'rien a retourner']);
    }


    #[Route('/bien/immo/{prix}', name: 'app_prix_bien_immo')]
    public function show_by_price(BienImmoRepository $bienImmoRepository,float $prix): Response
    {
        $biens = $bienImmoRepository->findByPrix($prix);

        return $this->json([
            'biens' => $biens,
        ]);
    }

    #[Route('/bien/immo/delete/{id}', name: 'app_delete_bienImmo',methods: ['POST'])]
    public function Delete (Request $request, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,int $id): Response
    {
        $bien = $bienImmoRepository->find($id);

        $bien->setDeletedAt(new \DateTimeImmutable());
        $entityManager->persist($bien);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

}
