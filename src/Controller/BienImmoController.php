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
use App\Repository\AddresseRepository;
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
        $biens = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/user', name: 'app_user_bien_immo')]
    public function findByUser(#[CurrentUser] User $user, BienImmoRepository $bienImmoRepository): Response
    {
        $biens = $bienImmoRepository->findBy(['utilisateur' => $user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
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
    // #[IsGranted('ROLE_USER')]
    public function createBienImmo(
        #[CurrentUser] User $user,
        Request $request, EntityManagerInterface $entityManager,PaysRepository $paysRepository, RegionRepository $regionRepository,
        CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,FileUploader $fileUploader ): Response 
    {
        $pays = $paysRepository->findAll();
        $region = $regionRepository->findAll();
        $data = json_decode($request->getContent(), true);

        $commoditeId = $data['commodite'];
        $typeId = $data['type'];
        $communeId = $data['commune'];
        $type = $typeImmoRepository->find($typeId);
        $commune = $communeRepository->find($communeId);
        $adresse = new Adresse();
        $immo = new BienImmo();
        $immo->setNbPiece($data['nb_piece']);
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
        $images = $request->files->get('photo');

        if ($images != null) {
            foreach ($images as $image) {
                $imageFileName = $fileUploader->upload($image);
                
                $photo = new PhotoImmo();
                $photo->setNom($imageFileName);
                $immo->addPhotoImmo($photo);
                $entityManager->persist($photo);
            }
        }
        

        $adresse->setQuartier($data['quartier']);
        $adresse->setRue($data['rue']);
        $adresse->setPorte($data['porte']);
        $adresse->setCommune($commune);

        $immo->setAdresse($adresse);
        $immo->setUtilisateur($user);
        // $commodites->addBienImmo($immo);//
        foreach ($commoditeId as $id) {
            $commodite = $commoditeRepository->find($id);
            
            if ($commodite !== null) {
                $immo->addCommodite($commodite);
            }
        }

        if ($request->getMethod() == Request::METHOD_POST) {
            $entityManager->getConnection()->beginTransaction();
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

        return $this->json(['message' => 'il y a une erreur ']);
    }

    #[Route('/bien/immo/type/{id}', name: 'app_type_bien_immo')]
    public function show_by_type(BienImmoRepository $bienImmoRepository,TypeImmoRepository $typeImmoRepository,int $id): Response
    {
        $type = $typeImmoRepository->find($id);
        $biens = $bienImmoRepository->findBy(['typeImmo' => $type, 'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
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

    #[Route('/bien/immo/region/{id}', name: 'app_bien_immo_region')]
    public function show_by_region(BienImmoRepository $bienImmoRepository,AdresseRepository $adresseRepository,
    CommuneRepository $communeRepository,RegionRepository $regionRepository,int $id): Response
    {
        $region = $regionRepository->find($id);
        $communes = $communeRepository->findBy(['region'=> $region->getId()]);

        // $address = [];
        $biens = [];
        foreach($communes as $commune){
            $communeId = $commune->getId();
            // $adresse = $adresseRepository->findByCommune($communeId);
            $adresses = $adresseRepository->findBy(['commune' => $communeId]);
            

            foreach ($adresses as $adresse) {
                $adresseId = $adresse->getId();
                $bienImmo = $bienImmoRepository->findBienByCommune($adresseId, $communeId);

                foreach ($bienImmo as $bien) {
                    $biens[] = $bien;
                }
            }
        }
        $response = new Response(json_encode(['biens' => $biens]));
        return $response;
    }

    #[Route('/bien/immo/commodite/{id}', name: 'app_commodite_bien_immo')]
    public function show_by_commodite(EntityManagerInterface $entityManager,int $id): Response
    {
        // $commodite = $entityManager->getRepository(Commodite::class)->find($id);
        // $biens = $commodite->getBienImmos();

        $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('b')
        ->innerJoin('b.commodites', 's', 'WITH', 's.id = :commoditeId')
        ->andWhere('b.deletedAt IS NULL')
        ->andWhere('b.is_rent = false')
        ->andWhere('b.is_sell = false')
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
        $bienImmo = $bienImmoRepository->findBy(['id'=>$id,'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $biens[] = $bien;
            }
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/bien/immo/statut/{statut}', name: 'app_statut_bien_immo')]
    public function show_by_statut(EntityManagerInterface $entityManager,string $statut): Response
    {
        // $biens = $bienImmoRepository->findByStatut($statut);

        $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('o')
           ->andWhere('o.statut LIKE :statut')
           ->andWhere('o.deletedAt IS NULL')
           ->andWhere('o.is_rent = false')
           ->andWhere('o.is_sell = false')
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
           ->andWhere('o.deletedAt IS NULL')
           ->andWhere('o.is_rent = false')
           ->andWhere('o.is_sell = false')
           ->setParameter('nom', '%'.$nom.'%')
           ->setMaxResults(10)
           ->getQuery()
           ->getResult();

        //    $tp = $entityManager->getRepository(TypeImmo::class)->findBy(['nom'=> $nom]);


           $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('b')
           ->andWhere('b.typeImmo = :typImmo')
           ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.is_rent = false')
            ->andWhere('b.is_sell = false')
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
    public function Delete (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,int $id): Response
    {
        // $bien = $bienImmoRepository->find($id);

        $bien = $bienImmoRepository->findOneBy(['id' => $id,'utilisateur'=> $user->getId(),'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);

        $bien->setDeletedAt(new \DateTimeImmutable());
        $entityManager->persist($bien);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    #[Route('/bien/immo/update/{id}', name: 'app_update_bien_immo',methods: ['POST'])]
    public function UpdateBienImmo (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    Request $request, CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,
    AdresseRepository $addresseRepository,FileUploader $fileUploader,int $id): Response
    {

        $bien = $bienImmoRepository->findOneBy(['id' => $id,'utilisateur'=> $user->getId(),'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);

        if ($bien === null) {
            return $this->json(['message' => 'Le bien n\'existe pas'], Response::HTTP_NOT_FOUND);
        }
        

        $data = json_decode($request->getContent(), true);

        $adresseId = $bien->getAdresse();

        $commoditeId = $data['commodite'];
        $typeId = $data['type'];
        $communeId = $data['commune'];
        $type = $typeImmoRepository->find($typeId);
        $commune = $communeRepository->find($communeId);
        $adresse = $addresseRepository->findOneBy(['id' => $adresseId]);
        $photo = new PhotoImmo();
        $bien->setNbPiece($data['nb_piece']);
        $bien->setNom($data['nom']);
        $bien->setChambre($data['chambre']);
        $bien->setCuisine($data['cuisine']);
        $bien->setToilette($data['toilette']);
        $bien->setSurface($data['surface']);
        $bien->setPrix($data['prix']);
        $bien->setStatut($data['statut']);
        $bien->setDescription($data['description']);
        foreach ($commoditeId as $id) {
            $commodite = $commoditeRepository->find($id);
            
            if ($commodite !== null) {
                $bien->addCommodite($commodite);
            }
        }
        $bien->setTypeImmo($type);
        $bien->setUpdateAt(new \DateTimeImmutable());
        $images = $request->files->get('photo');
        if ($images != null) {
            foreach ($images as $image) {
                $imageFileName = $fileUploader->upload($image);
                
                $photo = new PhotoImmo();
                $photo->setNom($imageFileName);
                $bien->addPhotoImmo($photo);
                $entityManager->persist($photo);
            }
        }
        

        $adresse->setQuartier($data['quartier']);
        $adresse->setRue($data['rue']);
        $adresse->setPorte($data['porte']);
        $adresse->setCommune($commune);

        $bien->setAdresse($adresse);
        $bien->setUtilisateur($user);


        if ($request->getMethod() == Request::METHOD_POST) {
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($bien);
                $entityManager->persist($adresse);
                $entityManager->flush();
                $entityManager->commit();
                
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }
            return $this->json(['message' => 'Le bien a été mis a jour avec succès'], Response::HTTP_OK);
        }

        return $this->json(['message' => 'il y a une erreur ']);
    }

    #[Route('/bien/immo/get/rent', name: 'app_bien_immo_rent',methods: ['GET'])]
    public function getBienRent(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $biens[] = $bien;
            }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }


    #[Route('/bien/immo/get/sell', name: 'app_bien_immo_sell',methods: ['GET'])]
    public function getBienSell(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $biens[] = $bien;
            }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }


    #[Route('/bien/immo/photo/update/{id}', name: 'app_update_photo_bien_immo',methods: ['POST'])]
    public function UpdateBienImmoPhoto (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    Request $request,FileUploader $fileUploader,int $id): Response
    {

        $bien = $bienImmoRepository->findOneBy(['id' => $id,'utilisateur'=> $user->getId(),'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);

        if ($bien === null) {
            return $this->json(['message' => 'Le bien n\'existe pas'], Response::HTTP_NOT_FOUND);
        }
        

        $images = $request->files->get('photo');

        if ($images != null) {
            foreach ($images as $image) {
                $imageFileName = $fileUploader->upload($image);
                
                $photo = new PhotoImmo();
                $photo->setNom($imageFileName);
                $bien->addPhotoImmo($photo);
                $entityManager->persist($photo);
            }
            $entityManager->persist($bien);
            $entityManager->flush();
            
            return $this->json(['message' => 'Photos enregistrées avec succès'], Response::HTTP_OK);
        }

        return $this->json(['message' => 'Aucune photo à enregistrer'], Response::HTTP_BAD_REQUEST);
    }
}
