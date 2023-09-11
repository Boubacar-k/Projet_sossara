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
use App\Entity\Favoris;
use App\Repository\BienImmoRepository;
use App\Repository\PeriodeRepository;
use App\Repository\PhotoImmoRepository;
use App\Repository\TypeImmoRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\PaysRepository;
use App\Repository\RegionRepository;
use App\Repository\CommuneRepository;
use App\Repository\AdresseRepository;
use App\Repository\CommoditeRepository;
use App\Repository\FavorisRepository;
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
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;

#[Route('/api', name: 'api_')]
class BienImmoController extends AbstractController
{
    const ATTRIBUTES_TO_SERIALIZE = ['statut','somme','createdAt','bien'=>['id','nb_piece','surface','chambre','photos',
    'cuisine','toilette','description','typeImmo','adresse','commodite'],
    'utilisateur'=>['nom','email','telephone','photo']];

    // LA LISTE DE TOUS LES BIENS PUBLIER
    #[Route('/bien/immo', name: 'app_bien_immo')]
    public function index(Request $request,BienImmoRepository $bienImmoRepository): Response
    {
        $biens = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    // TOTAL DE TOUS LES BIENS PUBLIER
    #[Route('/bien/immo/all', name: 'app_bien_immo_all',methods: ['GET'])]

    public function all(BienImmoRepository $bienImmoRepository): Response
    {
        $biens = $bienImmoRepository->findBy(['deletedAt' => null]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }
    // LA LISTE DE TOUS LES BIENS PUBLIER PAR L'UTILISATEUR CONNECTE
    #[Route('/bien/immo/user', name: 'app_user_bien_immo')]
    public function findByUser(#[CurrentUser] User $user, BienImmoRepository $bienImmoRepository): Response
    {
        $biens = $bienImmoRepository->findBy(['utilisateur' => $user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $response = new Response(json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    #[Route('/type/immo', name: 'app_type_immo')]
    public function type(Request $request,PaysRepository $paysRepository, RegionRepository $regionRepository,
    CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,PeriodeRepository $periodeRepository): Response
    {
        $type = $typeImmoRepository->findAll();
        $pays = $paysRepository->findAll();
        $region = $regionRepository->findAll();
        $commodite = $commoditeRepository->findAll();
        $commune = $communeRepository->findAll();
        $periode = $periodeRepository->findAllExceptThis(6);
        $response = new Response(json_encode( array( 'type' => $type,'pays' => $pays,
        'region' => $region,
        'commodite' => $commodite,
        'commune' => $commune, 'periode' => $periode  ) ) );
        return $response;
    }
    // ENDPOINT POUR L'ENREGISTREMENT OU PUBLICATION D'UN BIEN
    #[Route('/bien/immo/new', name: 'app_new_immo')]
    // #[IsGranted('ROLE_USER')]
    public function createBienImmo(
        #[CurrentUser] User $user,
        Request $request, EntityManagerInterface $entityManager,PaysRepository $paysRepository, RegionRepository $regionRepository,
        CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,PeriodeRepository $periodeRepository,
        FileUploader $fileUploader ): Response 
    {
        $pays = $paysRepository->findAll();
        $region = $regionRepository->findAll();

        $commodites = $request->get('commodite');
        $periodeId = $request->request->get('periode');
        $typeId = $request->request->get('type');
        $communeId = $request->request->get('commune');
        $type = $typeImmoRepository->find($typeId);
        $periode = $periodeRepository->find($periodeId);
        $commune = $communeRepository->find($communeId);
        $adresse = new Adresse();
        $immo = new BienImmo();
        $immo->setNbPiece($request->request->get('nb_piece'));
        $immo->setNom($request->request->get('nom'));
        $immo->setChambre($request->request->get('chambre'));
        $immo->setCuisine($request->request->get('cuisine'));
        $immo->setToilette($request->request->get('toilette'));
        $immo->setSurface($request->request->get('surface'));
        $immo->setPrix($request->request->get('prix'));
        $immo->setStatut($request->request->get('statut'));
        $immo->setDescription($request->request->get('description'));
        $immo->setTypeImmo($type);
        $immo->setPeriode($periode);
        $immo->setCreatedAt(new \DateTimeImmutable());
        $immo->setUpdateAt(new \DateTimeImmutable());
        if ($request->files->has('photo')) {
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
        }
        

        $adresse->setQuartier($request->request->get('quartier'));
        $adresse->setRue($request->request->get('rue'));
        $adresse->setPorte($request->request->get('porte'));
        $adresse->setLongitude($request->request->get('longitude'));
        $adresse->setLatitude($request->request->get('latitude'));
        $adresse->setCommune($commune);

        $immo->setAdresse($adresse);
        $immo->setUtilisateur($user);
        // $commodites->addBienImmo($immo);//
        if($commodites !=null){
            foreach ($commodites as $id) {
                $commodite = $commoditeRepository->find($id);
                
                if ($commodite !== null) {
                    $immo->addCommodite($commodite);
                }
            }
        }else{
            return $this->json(['message' => 'null est envoyer']);
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
            return $this->json(['message' => 'Le bien a été ajouté avec succès'], Response::HTTP_OK);
        }

        return $this->json(['message' => 'il y a une erreur ']);
    }

    // LISTE DES BIENS EN FONCTION DE L'ID DU TYPE
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
    // LISTE DES BIENS EN FONCTION DE LA COMMUNE
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

    // LISTE DES BIENS EN FONCTION DE LA REGION
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

    // LISTE DES BIENS EN FONCTION DES COMMODITES
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

    // LISTE DES BIENS EN FONCTION DU NOMBRE DE SALON
    #[Route('/bien/immo/piece/{piece}', name: 'app_piece_bien_immo')]
    public function show_by_piece(BienImmoRepository $bienImmoRepository,int $piece): Response
    {
        $biens = $bienImmoRepository->findByPiece($piece);
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    // AFFICHER LES DETAILS D'UN BIEN PARTICULIER
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

    // LISTE DES BIENS EN FONCTION DU STATUT
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
           ->getQuery()
           ->getResult();

        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
    }

    // LISTE DES BIENS EN FONCTION DU NOM DU TYPE
    #[Route('/bien/immo/nom/{nom}', name: 'app_nom_bien_immo')]
    public function show_by_nom(EntityManagerInterface $entityManager,string $nom): Response
    {
        $type = $entityManager->getRepository(TypeImmo::class)->createQueryBuilder('o')
           ->andWhere('o.nom LIKE :nom')
           ->andWhere('o.deletedAt IS NULL')
           ->andWhere('o.is_rent = false')
           ->andWhere('o.is_sell = false')
           ->setParameter('nom', '%'.$nom.'%')
           ->getQuery()
           ->getResult();

        //    $tp = $entityManager->getRepository(TypeImmo::class)->findBy(['nom'=> $nom]);


           $biens = $entityManager->getRepository(BienImmo::class)->createQueryBuilder('b')
           ->andWhere('b.typeImmo = :typImmo')
           ->andWhere('b.deletedAt IS NULL')
            ->andWhere('b.is_rent = false')
            ->andWhere('b.is_sell = false')
           ->setParameter('typImmo', $type)
           ->getQuery()
           ->getResult();

        // $type = $entityManager->getRepository(TypeImmo::class)->findBy(['nom'=> $nom]);
        // $biens = $type->getBienImmo();
        // dd($biens);
        $response = new Response( json_encode( array( 'biens' => $biens ) ) );
        return $response;
        return $this->json(['message'=>'rien a retourner']);
    }

    // LISTE DES BIENS EN FONCTION DU PRIX
    #[Route('/bien/immo/{prix}', name: 'app_prix_bien_immo')]
    public function show_by_price(BienImmoRepository $bienImmoRepository,float $prix): Response
    {
        $biens = $bienImmoRepository->findByPrix($prix);

        return $this->json([
            'biens' => $biens,
        ]);
    }

    // SUPPRIMER UN BIEN
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

    // MODIFIER UN BIEN
    #[Route('/bien/immo/update/{id}', name: 'app_update_bien_immo',methods: ['POST'])]
    public function UpdateBienImmo (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    Request $request, CommuneRepository $communeRepository, TypeImmoRepository $typeImmoRepository, CommoditeRepository $commoditeRepository,
    AdresseRepository $addresseRepository,PhotoImmoRepository $photoImmoRepository,PeriodeRepository $periodeRepository,FileUploader $fileUploader,int $id): Response
    {

        $bien = $bienImmoRepository->findOneBy(['id' => $id,'utilisateur'=> $user->getId(),'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);
        if ($bien === null) {
            return $this->json(['message' => 'Le bien n\'existe pas'], Response::HTTP_NOT_FOUND);
        }
        

        $data = json_decode($request->getContent(), true);

        $adresseId = $bien->getAdresse();

        $commoditeId = $request->get('commodite');
        $periodeId = $request->request->get('periode');
        $typeId = $request->request->get('type');
        $communeId = $request->request->get('commune');
        $type = $typeImmoRepository->find($typeId);
        $periode = $periodeRepository->find($periodeId);
        $commune = $communeRepository->find($communeId);
        $adresse = $addresseRepository->findOneBy(['id' => $adresseId]);
        $photo = new PhotoImmo();

        $bien->setNbPiece($request->request->get('nb_piece'));
        $bien->setNom($request->request->get('nom'));
        $bien->setChambre($request->request->get('chambre'));
        $bien->setCuisine($request->request->get('cuisine'));
        $bien->setToilette($request->request->get('toilette'));
        $bien->setSurface($request->request->get('surface'));
        $bien->setPrix($request->request->get('prix'));
        $bien->setStatut($request->request->get('statut'));
        $bien->setDescription($request->request->get('description'));

        if($commoditeId !=null){
            foreach ($commoditeId as $id) {
                $commodite = $commoditeRepository->find($id);
                
                if ($commodite !== null) {
                    $bien->addCommodite($commodite);
                }
            }
        }else{
            return $this->json(['message' => 'null est envoyer']);
        }
        $bien->setTypeImmo($type);
        $bien->setPeriode($periode);
        $bien->setUpdateAt(new \DateTimeImmutable());
        if ($request->files->has('photo')) {
            $images = $request->files->get('photo');
            if ($images != null) {
                foreach ($images as $image) {
                    $imageFileName = $fileUploader->upload($image);
                    
                    $photos = $photoImmoRepository->findBy(['bien' => $bien->getId()]);
                    foreach ($photos as $photo) {
                        $photo->setNom($imageFileName);
                        $bien->addPhotoImmo($photo);
                        $entityManager->persist($photo);
                    }
                }
            }
        }
        

    
        $adresse->setQuartier($request->request->get('quartier'));
        $adresse->setRue($request->request->get('rue'));
        $adresse->setPorte($request->request->get('porte'));
        $adresse->setLongitude($request->request->get('longitude'));
        $adresse->setLatitude($request->request->get('latitude'));
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

    // LISTE DES BIENS EN LOCATION EN FONCTION DE L'UTILISATEUR CONNECTE (BAILLEUR/PROPRIETAIRE/AGENCE NB: CELUI A QUI LE BIEN APPARTIENT)
    #[Route('/bien/immo/get/rent', name: 'app_bien_immo_rent',methods: ['GET'])]
    public function getBienRent(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
                foreach($transaction as $transac){
                    $biens[] = $transac;
                }
            }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    // LISTE DES BIENS LOUES EN FONCTION DE L'UTILISATEUR CONNECTE (LOCATAIRE NB: LES BIENS QUE L'UTILISATEUR CONNECTE A LOUES)
    #[Route('/bien/immo/get/rent/mine', name: 'app_bien_immo_rent_mine',methods: ['GET'])]
    public function getBienRentByuser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $transaction = $transactionRepository->findBy(['utilisateur' => $user,'bien'=>$bien,'isDeleted' => false]);
                foreach($transaction as $transac){
                    $biens[] = $transac;
                }
            }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    //TOTAL DES BIENS LOUES
    #[Route('/bien/immo/get/rent/all', name: 'app_bien_immo_rent_all',methods: ['GET'])]
    public function getBienAllRent(BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->count(['deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $response = new Response( json_encode( array( 'biens' => $bienImmo) ) );
        return $response;
    }

    //TOTAL DES BIENS VENDUS
    #[Route('/bien/immo/get/sell/all', name: 'app_bien_immo_sell_all',methods: ['GET'])]
    public function getBienAllSell(BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->count(['deletedAt' => null,'is_rent' => false,'is_sell' => true]);        
        $response = new Response( json_encode( array( 'biens' => $bienImmo) ) );
        return $response;
    }

    // LISTE DES BIENS ACHETES EN FONCTION DE L'UTILISATEUR CONNECTE (ACHETEUR NB: LES BIENS QUE L'UTILISATEUR CONNECTE A ACHETES)
    #[Route('/bien/immo/get/sell/mine', name: 'app_bien_immo_sell_mine',methods: ['GET'])]
    public function getBienSellByuser(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        $biens= [];

            foreach ($bienImmo as $bien) {
                $transaction = $transactionRepository->findBy(['utilisateur' => $user,'bien'=>$bien,'isDeleted' => false]);
                foreach($transaction as $transac){
                    $biens[] = $transac;
                }
            }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    // LISTE DES BIENS VENDUS EN FONCTION DE L'UTILISATEUR CONNECTE (BAILLEUR/PROPRIETAIRE/AGENCE NB: LES BIENS QUE L'UTILISATEUR CONNECTE A VENDUS)
    #[Route('/bien/immo/get/sell', name: 'app_bien_immo_sell',methods: ['GET'])]
    public function getBienSell(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,TransactionRepository $transactionRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['utilisateur'=>$user->getId(),'deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        $biens= [];

        foreach ($bienImmo as $bien) {
            $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
            foreach($transaction as $transac){
                $biens[] = $transac;
            }
        }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    // AFFICHER LA FACTURE D'UN BIEN ACHETE
    #[Route('/bien/immo/get/sell/invoyce/{id}', name: 'app_bien_immo_sell_invoyce',methods: ['GET'])]
    public function getBienSellForInvoyce(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,
    TransactionRepository $transactionRepository, int $id): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => false,'is_sell' => true]);
        $biens= [];

        foreach ($bienImmo as $bien) {
            $transaction = $transactionRepository->findBy(['utilisateur'=>$user->getId(),'id'=> $id,'bien'=>$bien->getId(),'isDeleted' => false]);
            foreach($transaction as $transac){
                $biens[] = $transac;
            }
        }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    // AFFICHER LA FACTURE D'UN BIEN LOUE
    #[Route('/bien/immo/get/rent/invoyce/{id}', name: 'app_bien_immo_rent_invoyce',methods: ['GET'])]
    public function getBienRentForInvoyce(#[CurrentUser] User $user,BienImmoRepository $bienImmoRepository,
    TransactionRepository $transactionRepository, int $id): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => true,'is_sell' => false]);
        $biens= [];

        foreach ($bienImmo as $bien) {
            $transaction = $transactionRepository->findBy(['utilisateur'=>$user->getId(),'id'=> $id,'bien'=>$bien->getId(),'isDeleted' => false]);
            foreach($transaction as $transac){
                $biens[] = $transac;
            }
        }
        
        $response = new Response( json_encode( array( 'biens' => $biens) ) );
        return $response;
    }

    // MODIFIER LES PHOTOS D'UN BIEN
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

    // AJOUTER/SUPPRIMER UN BIEN COMME FAVORIS
    #[Route('/bien/immo/view/{id}', name: 'app_view_bien_immo',methods: ['POST'])]
    public function View (#[CurrentUser] User $user,FavorisRepository $favorisRepository , EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,
    Request $request,int $id): Response
    {
        $bien = $bienImmoRepository->findOneBy(['id' => $id,'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);
        if ($bien === null) {
            return $this->json(['message' => 'Le bien n\'existe pas'], Response::HTTP_NOT_FOUND);
        }
        $favorisExist = $favorisRepository->findOneBy(['bien'=>$bien,'utilisateur'=>$user]);

        if($favorisExist){
            $entityManager->remove($favorisExist);
            $entityManager->flush();
            return $this->json(['message' => 'bien supprime de vos favoris'], Response::HTTP_OK);
        }
        else{
            $favoris = new Favoris();

            $favoris->setBien($bien);
            $favoris->setUtilisateur($user);

            if ($request->getMethod() == Request::METHOD_POST) {
                $entityManager->getConnection()->beginTransaction();
                try {
    
                    $entityManager->persist($favoris);
                    $entityManager->flush();
                    $entityManager->commit();
                    
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    throw $e;
                }
                return $this->json(['message' => 'vous avez aime ce bien'], Response::HTTP_OK);
            }
    
            return $this->json(['message' => 'il y a une erreur ']);
        }
    }


    // AFFICHER LE NOMBRE DE VUE (FAVORIS) PAR BIEN
    #[Route('/bien/immo/views/get/{id}', name: 'app_bien_immo_get_views',methods: ['GET'])]
    public function getViews(BienImmoRepository $bienImmoRepository,FavorisRepository $favorisRepository, int $id): Response
    {
        $bienImmo = $bienImmoRepository->findOneBy(['id' => $id,'deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $favoris = $favorisRepository->count(['bien' => $bienImmo]);
        
        $response = new Response( json_encode( array( 'vues' => $favoris) ) );
        return $response;
    }

    // AFFICHER LE NOMBRE DE VUE (FAVORIS) PAR BIEN
    #[Route('/bien/immo/favoris/get', name: 'app_bien_immo_get_favorisList',methods: ['GET'])]
    public function favorisList(BienImmoRepository $bienImmoRepository,FavorisRepository $favorisRepository): Response
    {
        $bienImmo = $bienImmoRepository->findBy(['deletedAt' => null,'is_rent' => false,'is_sell' => false]);
        $biensAvecFavoris = [];

        foreach ($bienImmo as $bien) {
            // Comptez le nombre de favoris pour ce bien
            $nombreFavoris = $favorisRepository->findBy(['bien' => $bien]);

            foreach($nombreFavoris as $favoris){
                $biensAvecFavoris[]=$nombreFavoris;
            }
        }
        
        $response = new Response( json_encode( array( 'vues' => $biensAvecFavoris) ) );
        return $response;
    }
    // AFFICHER LE NOMBRE DE VUE (FAVORIS) PAR BIEN
    #[Route('/bien/immo/most/views/get', name: 'app_bien_immo_get_most_views',methods: ['GET'])]
    public function getMoreViews(BienImmoRepository $bienImmoRepository,FavorisRepository $favorisRepository): Response
    {
        $biensImmo = $bienImmoRepository->findBy(['deletedAt' => null, 'is_rent' => false, 'is_sell' => false]);

        // Créez un tableau pour stocker le nombre de favoris pour chaque bien
        $biensAvecFavoris = [];

        foreach ($biensImmo as $bien) {
            // Comptez le nombre de favoris pour ce bien
            $nombreFavoris = $favorisRepository->count(['bien' => $bien]);

            // Stockez le bien et le nombre de favoris dans un tableau associatif
            $biensAvecFavoris[] = [
                'bien' => $bien,
                'nombreFavoris' => $nombreFavoris,
            ];
        }

        // Triez le tableau en fonction du nombre de favoris en ordre décroissant
        usort($biensAvecFavoris, function ($a, $b) {
            return $b['nombreFavoris'] - $a['nombreFavoris'];
        });

        // Sélectionnez les 3 premiers biens triés (les plus aimés)
        $biensLesPlusAimes = array_slice($biensAvecFavoris, 0, 6);

        // Créez un tableau pour stocker uniquement les biens (sans le nombre de favoris)
        $biensPourReponse = [];

        foreach ($biensLesPlusAimes as $bienAime) {
            $biensPourReponse[] = $bienAime['bien'];
        }

        // Créez une réponse JSON avec les biens les plus aimés
        $response = new Response(json_encode(['biens' => $biensPourReponse,'favoris' => $nombreFavoris]));
        return $response;
    }

    // AFFICHER LE NOMBRE DE VUE (FAVORIS) PAR BIEN EN FONCTION DE L'UTILISATEUR
    #[Route('/bien/immo/views/user/get', name: 'app_bien_immo_user_get_views',methods: ['GET'])]
    public function getViewsByUeser(#[CurrentUser] User $user,FavorisRepository $favorisRepository): Response
    {
        $favoris = $favorisRepository->findBy(['utilisateur' => $user]);
        $biens= [];
        foreach($favoris as $bien){
            $biens[] = $bien;
        }
        
        $response = new Response( json_encode( array( 'vues' => $biens) ) );
        return $response;
    }

// ----------------------------------   ---------------  CETTE PARTIE CONCERNE L'AGENCE ET LES AGENTS  ---------------  ------------------------------------------------------

    // AFFICHER LES BIENS EN FONCTION DE L'AGENCE ET DE SES AGENTS
    #[Route('/bien/immo/user/child/get/{id}', name: 'app_get_bien_agent_by_agence',methods: ['GET'])]
    public function getBienAgentByAgence (UserRepository $userRepository,int $id,BienImmoRepository $bienImmoRepository): Response
    {

        $agents = $userRepository->findBy(['parent'=>$id]);
        $user = $userRepository->find($id);

        $biens = [];
        foreach($agents as $agent){
            $BienImmo = $bienImmoRepository->findBy(['utilisateur' => $agent]);
            foreach($BienImmo as $b){
                $biens[] = $b;
            }
    
        }

        $bien_immo = $bienImmoRepository->findBy(['utilisateur' => $id]);

        $bien_immos = [];
        foreach($bien_immo as $bien){
            $bien_immos[] = $bien;
        }

        $userInfo = [
            'id' => $user->getId(),
            'username' => $user->getnom(),
            'email' => $user->getEmail(),
            'date_de_naissance' => $user->getDateNaissance(),
            'telephone' => $user->getTelephone(),
            'role' => $user->getRoles(),
            'photo' => $user->getPhoto(),
            'documents' => [],
        ];

        foreach ($user->getDocuments() as $document) {
            $photos = [];
            foreach ($document->getPhotoDocuments() as $photoDocument) {
                $photos[] = [
                    'id' => $photoDocument->getId(),
                    'nom' => $photoDocument->getNom(),
                ];
            }
            $documentInfo = [
                'id' => $document->getId(),
                'nom' => $document->getNom(),
                'num_doc'=> $document->getNumDoc(),
                'photo' => $photos,
            ];
            $userInfo['documents'][] = $documentInfo;
        }


        $response = new Response( json_encode( array('biens_agence' => $bien_immos, 'biens_agents' => $biens, 'utilisateur' => $userInfo ) ) );
        return $response;
    }


    // AFFICHER LES BIENS EN FONCTION DE L'ID DE AGENT
    #[Route('/bien/immo/user/agent/get/{id}', name: 'app_get_bien_by_agent',methods: ['GET'])]
    public function getBienByAgent (int $id,BienImmoRepository $bienImmoRepository): Response
    {
        $bien_immo = $bienImmoRepository->findBy(['utilisateur' => $id]);

        $bien_immos = [];
        foreach($bien_immo as $bien){
            $bien_immos[] = $bien;
        }


        $response = new Response( json_encode( array('biens_agence' => $bien_immos) ) );
        return $response;
    }


    // AFFICHER LES BIENS EN FONCTION DE L'AGENCE CONNECTE ET DE SES AGENTS
    #[Route('/bien/immo/user/agence/get', name: 'app_get_bien_by_agence',methods: ['GET'])]
    public function getBienByAgence (#[CurrentUser] User $user,UserRepository $userRepository,BienImmoRepository $bienImmoRepository): Response
    {

        $agent = $userRepository->findBy(['parent'=>$user]);
        $bien_immo = $bienImmoRepository->findBy(['utilisateur' => $user]);

        $bien_immos = [];
        foreach($bien_immo as $bien){
            $bien_immos[] = $bien;
        }

        $biens = [];
        foreach($agent as $agt){
            $BienImmo = $bienImmoRepository->findBy(['utilisateur' => $agt]);
            foreach($BienImmo as $bien){
                $biens[] = $bien;
            }
    
        }

        $response = new Response( json_encode( array( 'biens_agents' => $biens, 'biens_agences' => $bien_immos) ) );
        return $response;
    }

}
