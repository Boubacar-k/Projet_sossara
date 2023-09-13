<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Commodite;
use App\Entity\Commune;
use App\Entity\Document;
use App\Entity\Pays;
use App\Entity\Periode;
use App\Entity\PhotoDocument;
use App\Entity\Region;
use App\Entity\TypeImmo;
use App\Entity\TypeProbleme;
use App\Entity\TypeTransaction;
use App\Entity\User;
use App\Entity\UserAdresse;
use App\Repository\BienImmoRepository;
use App\Repository\BlogRepository;
use App\Repository\CommoditeRepository;
use App\Repository\CommuneRepository;
use App\Repository\DocumentRepository;
use App\Repository\PaysRepository;
use App\Repository\PeriodeRepository;
use App\Repository\ProblemeRepository;
use App\Repository\RegionRepository;
use App\Repository\TransactionRepository;
use App\Repository\TypeImmoRepository;
use App\Repository\TypeProblemeRepository;
use App\Repository\TypeTransactionRepository;
use App\Repository\UserAdresseRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/admin', name: 'api_admin')]
#[AttributeIsGranted('ROLE_SUPER_ADMIN')]
class AdminController extends AbstractController
{
    // CREER UN UTILISATEUR
    #[Route('/user/create', name: 'app_create_user', methods: ['POST'],)]
    public function createUser(#[CurrentUser] User $admin,Request $request, UserPasswordHasherInterface $userPasswordHasher,FileUploader $fileUploader,
    EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $document = new Document();

        $user->setNom($request->request->get('nom'));
        $user->setEmail($request->request->get('email'));
        $newRoles = $request->request->get('roles');

        $newRoles = $newRoles;
    
        if ($newRoles === 'PROPRIETAIRE') {
            $user->setRoles(['ROLE_PROPRIETAIRE']);
        } elseif ($newRoles === 'ADMINISTRATEUR') {
            $user->setRoles(['ROLE_ADMIN']);
        }elseif ($newRoles === 'AGENCE') {
            $user->setRoles(['ROLE_AGENCE']);
        }elseif ($newRoles === 'LOCATAIRE OU ACHETEUR') {
            $user->setRoles(['ROLE_LOCATAIRE']);
        } else {

            return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user,$request->request->get('password')));
        $user->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        $user->setTelephone($request->request->get('telephone'));
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $user->setPhoto($imageFileName);
        }

        $user->setIsCertified(true);
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setUpdateAt(new \DateTimeImmutable());

        $document->setNumDoc($request->request->get('num_doc'));
        $document->setNom($request->request->get('nom_doc'));
        $images = $request->files->get('photo');
        if ($images != null) {
            foreach ($images as $image) {
                $imageFileName = $fileUploader->upload($image);
                
                    $photo = new PhotoDocument();
                    $photo->setNom($imageFileName);
                    $photo->setCreatedAt(new \DateTimeImmutable());
                    $photo->setUpdatedAt(new \DateTimeImmutable());
                    $document->addPhotoDocument($photo);
                    $entityManager->persist($photo);
                }
        }
        
        $user->addDocument($document);

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($user);
                $entityManager->persist($document);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
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

            return $this->json(['message' => 'Utilisateur cree avec succès','utilisteur' => $userInfo], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur d'inscription",
        ]);
    }

    // MODIFIER UN UTILISATEUR
    #[Route('/user/updtate/{id}', name: 'app_update_user', methods: ['POST'],)]
    public function modifyUser(#[CurrentUser] User $admin,Request $request, UserPasswordHasherInterface $userPasswordHasher,FileUploader $fileUploader,
    EntityManagerInterface $entityManager,UserRepository $userRepository,DocumentRepository $documentRepository,int $id): Response
    {
        $user = $userRepository->find($id);

        $user->setNom($request->request->get('nom'));
        $user->setEmail($request->request->get('email'));
        $newRoles = $request->request->get('roles');

        $newRoles = $newRoles;
    
        if ($newRoles === 'PROPRIETAIRE') {
            $user->setRoles(['ROLE_PROPRIETAIRE']);
        } elseif ($newRoles === 'ADMINISTRATEUR') {
            $user->setRoles(['ROLE_ADMIN']);
        }elseif ($newRoles === 'AGENCE') {
            $user->setRoles(['ROLE_AGENCE']);
        }elseif ($newRoles === 'LOCATAIRE OU ACHETEUR') {
            $user->setRoles(['ROLE_LOCATAIRE']);
        } else {

            return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }
        $user->setPassword($userPasswordHasher->hashPassword($user,$request->request->get('password')));
        $user->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
        $user->setTelephone($request->request->get('telephone'));
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $user->setPhoto($imageFileName);
        }

        $user->setIsCertified(true);
        $user->setIsVerified(true);
        $user->setUpdateAt(new \DateTimeImmutable());

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {

                $entityManager->persist($user);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            
            $userInfo = [
                'id' => $user->getId(),
                'username' => $user->getnom(),
                'email' => $user->getEmail(),
                'date_de_naissance' => $user->getDateNaissance(),
                'telephone' => $user->getTelephone(),
                'role' => $user->getRoles(),
                'photo' => $user->getPhoto()
            ];

            return $this->json(['message' => 'Utilisateur modifie avec succès','utilisateur' => $userInfo], Response::HTTP_OK);
        }

        return $this->json([
            'erreur' => "erreur de modification",
        ]);
    }
    // SUPPRIMER UN UTILISATEUR
    #[Route('/user', name: 'app_admin')]
    public function index(#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,
    BienImmoRepository $bienImmoRepository,int $id,TransactionRepository $transactionRepository): Response
    {
        $deleteUser = $userRepository->find($id);
        $bienImmo = $bienImmoRepository->findBy(['utilisateur' => $deleteUser]);

        foreach($bienImmo as $bien){
            $bien->setUtilisateur($user);
            $bien->setDeletedAt(new \DateTimeImmutable());
            $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
            foreach($transaction as $transac){
                $transac->setIsDeleted(true);
            }
        }
        $entityManager->remove($deleteUser);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // SUPPRIMER UN AGENT
    #[Route('/user/child/delete/{id}', name: 'app_delete_agent',methods: ['POST'])]
    public function DeleteAgent (#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,int $id,
    BienImmoRepository $bienImmoRepository,UserAdresseRepository $userAdresseRepository): Response
    {
        $agent = $userRepository->find($id);

        $adress = $userAdresseRepository->findOneBy(['utilisateur' => $agent->getId()]);
        $agence = $agent->getParent();
        $bienImmo = $bienImmoRepository->findBy(['utilisateur' => $agent]);

        foreach($bienImmo as $bien){
            $bien->setUtilisateur($agence);
        }
        $entityManager->remove($adress);
        $entityManager->remove($agent);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // MODIFIER LE STATUS D'UN UTILISATEUR
    #[Route('/user/setCertify/{id}', name: 'app_setCertication_user',methods: ['POST'])]
    public function userCertification (#[CurrentUser] User $user, EntityManagerInterface $entityManager,UserRepository $userRepository,int $id): Response
    {
        $user1 = $userRepository->find($id);

        
        if($user1->isIsCertified() == false){
            $user1->setIsCertified(true);
        }else{
            $user1->setIsCertified(false);
        }
        $entityManager->getConnection()->beginTransaction();
        try {

            $entityManager->persist($user1);
            $entityManager->flush();
            $entityManager->commit();
            
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
        return $this->json(['message' => 'Modification effectuee avec succès'], Response::HTTP_OK);
    }

    // BANIR D'UN UTILISATEUR
    #[Route('/user/banish/{id}', name: 'app_banish_user',methods: ['POST'])]
    public function userBanish (#[CurrentUser] User $user,MailerInterface $mailer, EntityManagerInterface $entityManager,UserRepository $userRepository,
    int $id): Response
    {
        $banishUser = $userRepository->find($id);

        
        if($banishUser->isVerified() == true){
            $banishUser->setIsVerified(false);
        }
        $entityManager->getConnection()->beginTransaction();
        try {

            $entityManager->persist($banishUser);
            $entityManager->flush();
            $entityManager->commit();
            
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }

        $email = (new TemplatedEmail())
                ->from(new Address('testappaddress00@gmail.com', 'Sossara Mail Bot'))
                ->to($banishUser->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('admin/banish_email.html.twig');

                $mailer->send($email);
        return $this->json(['message' => 'Vous avez banis cet utilisateur'], Response::HTTP_OK);
    }

    // SUPPRIMER UN BIEN
    #[Route('/bien/immo/delete/{id}', name: 'app_delete_bienImmo',methods: ['POST'])]
    public function Delete (#[CurrentUser] User $user, EntityManagerInterface $entityManager,BienImmoRepository $bienImmoRepository,int $id,
    TransactionRepository $transactionRepository): Response
    {
        $bien = $bienImmoRepository->findOneBy(['id' => $id,'deletedAt' => null, 'is_rent' => false,'is_sell' => false]);

        $bien->setUtilisateur($user);
            $bien->setDeletedAt(new \DateTimeImmutable());
            $transaction = $transactionRepository->findBy(['bien'=>$bien,'isDeleted' => false]);
            foreach($transaction as $transac){
                $transac->setIsDeleted(true);
            }
        $entityManager->persist($bien);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // LISTE DE TOUS LES AGENTS
    #[Route('/user/agent/get', name: 'app_all_agent_get',methods: ['GET'])]
    public function getAgent (#[CurrentUser] User $user,UserRepository $userRepository): Response
    {

        $agent = $userRepository->findUsersWithParent();
        $data = [];
        foreach ($agent as $user) {
            $data[] = [
                'id' => $user->getId(),
                'username' => $user->getnom(),
                'email' => $user->getEmail(),
                'date_de_naissance' => $user->getDateNaissance(),
                'telephone' => $user->getTelephone(),
                'role' => $user->getRoles(),
                'photo' => $user->getPhoto(),
                'agence' => $user->getParent(),
            ];
        }
        $response = new Response( json_encode( array( 'agents' => $data ) ) );
        return $response;
    }

     // CREER UN BLOG
     #[Route('/blog/add', name: 'app_add_new_blog', methods: ['POST'])]
     public function AddBlog(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager,FileUploader $fileUploader): Response
     {
         $blog = new Blog();
         $blog->setTitre($request->request->get('titre'));
         $blog->setDescription($request->request->get('description'));
         $blog->setUtilisateur($user);
         $image = $request->files->get('photo');
         if ($image) {
             $imageFileName = $fileUploader->upload($image);
             $blog->setPhoto($imageFileName);
         }
 
         if ($request->getMethod() == Request::METHOD_POST){
             $entityManager->getConnection()->beginTransaction();
             try {
                 $entityManager->persist($blog);
                 $entityManager->flush();
                 $entityManager->commit();
             } catch (\Exception $e) {
                 $entityManager->rollback();
                 throw $e;
             }
 
             return $this->json(['message' => 'Publication effectue avec succes'], Response::HTTP_OK);
         }
         return $this->json([
             'erreur' => "erreur de publication",
         ]);
     }

    // MODIFIER UN BLOG
    #[Route('/blog/update/{id}', name: 'app_update_blog', methods: ['POST'])]
    public function UpdateBlog(#[CurrentUser] User $user,Request $request,BlogRepository $blogRepository,EntityManagerInterface $entityManager,int $id,
    FileUploader $fileUploader): Response
    {
        $blog = $blogRepository->findOneBy(['id' => $id,'utilisateur' => $user]);
        $blog->setTitre($request->request->get('titre'));
        $blog->setDescription($request->request->get('description'));
        $blog->setUtilisateur($user);
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $blog->setPhoto($imageFileName);
        }

        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($blog);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // SUPPRIMER UN BLOG
    #[Route('/blog/delete/{id}', name: 'app_delete_blog', methods: ['POST'])]
    public function deleteBlog(#[CurrentUser] User $user,BlogRepository $blogRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $blog = $blogRepository->find($id);

        $entityManager->remove($blog);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }

    // CREER UN PAYS
    #[Route('/country/add', name: 'app_add_new_country', methods: ['POST'])]
    public function AddCountry(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager): Response
    {
        $pays = new Pays();
        $pays->setCodeIso($request->request->get('code_iso'));
        $pays->setIndicatif($request->request->get('indicatif'));
        $pays->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($pays);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de pays",
        ]);
    }

    // MODIFIER UN PAYS
    #[Route('/country/update/{id}', name: 'app_update_country', methods: ['POST'])]
    public function UpdateCountry(#[CurrentUser] User $user,Request $request,PaysRepository $paysRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $pays = $paysRepository->find($id);
        $pays->setCodeIso($request->request->get('code_iso'));
        $pays->setIndicatif($request->request->get('indicatif'));
        $pays->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($pays);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER UN PAYS
    #[Route('/country/get', name: 'app_get_country')]
    public function getPays(#[CurrentUser] User $user,PaysRepository $paysRepository): Response
    {
        $pays = $paysRepository->findAll();
        $response = new Response(json_encode( array( 'pays' => $pays) ) );
        return $response;
    }
    // // SUPPRIMER UN PAYS
    // #[Route('/country/delete/{id}', name: 'app_delete_country', methods: ['POST'])]
    // public function deletecountry(#[CurrentUser] User $user,PaysRepository $paysRepository,RegionRepository $regionRepository,CommuneRepository $communeRepository,
    // EntityManagerInterface $entityManager,int $id): Response
    // {
    //     $pays = $paysRepository->find($id);

    //     $entityManager->remove($pays);
    //     $entityManager->flush();

    //     return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    // }

    // CREER UNE REGION
    #[Route('/region/add/{id}', name: 'app_add_new_region', methods: ['POST'])]
    public function AddRegion(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager,PaysRepository $paysRepository,int $id): Response
    {
        $pays = $paysRepository->find($id);
        $region =  new Region();
        $region->setNom($request->request->get('nom'));
        $region->setPays($pays);
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($region);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de region",
        ]);
    }


    // MODIFIER UNE REGION
    #[Route('/region/update/{id}', name: 'app_update_regon', methods: ['POST'])]
    public function UpdateRegion(#[CurrentUser] User $user,Request $request,RegionRepository $regionRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $region = $regionRepository->find($id);
        $region->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($region);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER UN REGION
    #[Route('/region/get', name: 'app_get_region')]
    public function getRegion(#[CurrentUser] User $user,RegionRepository $regionRepository): Response
    {
        $region = $regionRepository->findAll();
        $response = new Response(json_encode( array( 'region' => $region) ) );
        return $response;
    }

    // CREER UNE COMMUNE
    #[Route('/commune/add/{id}', name: 'app_add_new_commune', methods: ['POST'])]
    public function AddCommune(#[CurrentUser] User $user,Request $request,RegionRepository $regionRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $commnue = new Commune();
        $region = $regionRepository->find($id);
        $commnue->setRegion($region);
        $commnue->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($commnue);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de commune",
        ]);
    }

    // MODIFIER UNE COMMUNE
    #[Route('/commune/update/{id}', name: 'app_update_commune', methods: ['POST'])]
    public function UpdateCommune(#[CurrentUser] User $user,Request $request,CommuneRepository $communeRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $commune = $communeRepository->find($id);
        $commune->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($commune);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER UN COMMUNE
    #[Route('/commune/get', name: 'app_get_commune')]
    public function getCommune(#[CurrentUser] User $user,CommuneRepository $communeRepository): Response
    {
        $commune = $communeRepository->findAll();
        $response = new Response(json_encode( array( 'commune' => $commune) ) );
        return $response;
    }

    // CREER UNE COMMODITE
    #[Route('/commodite/add', name: 'app_add_new_commodite', methods: ['POST'])]
    public function AddCommodite(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager,FileUploader $fileUploader): Response
    {
        $commodite = new Commodite();
        $commodite->setNom($request->request->get('nom'));
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $commodite->setIcone($imageFileName);
        }
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($commodite);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de commodite",
        ]);
    }

    // MODIFIER UNE COMMODITE
    #[Route('/commodite/update/{id}', name: 'app_update_commune', methods: ['POST'])]
    public function UpdateCommodite(#[CurrentUser] User $user,Request $request,CommoditeRepository $commoditeRepository,EntityManagerInterface $entityManager,
    FileUploader $fileUploader,int $id): Response
    {
        $commodite = $commoditeRepository->find($id);
        $commodite->setNom($request->request->get('nom'));
        $image = $request->files->get('photo');
        if ($image) {
            $imageFileName = $fileUploader->upload($image);
            $commodite->setIcone($imageFileName);
        }
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($commodite);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER UN COMMODITES
    #[Route('/commodite/get', name: 'app_get_commodite')]
    public function getCommodite(#[CurrentUser] User $user,CommoditeRepository $commoditeRepository): Response
    {
        $commodite = $commoditeRepository->findAll();
        $response = new Response(json_encode( array( 'commodite' => $commodite) ) );
        return $response;
    }


    // CREER UN TYPE DE BIEN
    #[Route('/type/bien/add', name: 'app_add_new_type_bien', methods: ['POST'])]
    public function AddTypeBien(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager): Response
    {
        $type = new TypeImmo();
        $type->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de type de bien",
        ]);
    }


    // MODIFIER UN TYPE DE BIEN
    #[Route('/type/bien/update/{id}', name: 'app_update_type_bien', methods: ['POST'])]
    public function UpdateTypeBien(#[CurrentUser] User $user,Request $request,TypeImmoRepository $typeImmoRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $type = $typeImmoRepository->find($id);
        $type->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER LES TYPES DE BIEN
    #[Route('/type/bien/get', name: 'app_get_type_probleme')]
    public function getTypeBien(#[CurrentUser] User $user,TypeImmoRepository $typeImmoRepository): Response
    {
        $type = $typeImmoRepository->findAll();
        $response = new Response(json_encode( array( 'type' => $type) ) );
        return $response;
    }

    // CREER UNE UN TYPE DE PROBLEME
    #[Route('/type/probleme/add', name: 'app_add_new_type_probleme', methods: ['POST'])]
    public function AddTypeProbleme(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager): Response
    {
        $type = new TypeProbleme();
        $type->setNom($request->request->get('nom'));
        $type->setCreatedAt(new \DateTimeImmutable());
        $type->setUpdatedAt(new \DateTimeImmutable());
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de type de probleme",
        ]);
    }

    // MODIFIER UN TYPE DE PROBLEME
    #[Route('/type/probleme/update/{id}', name: 'app_update_type_probleme', methods: ['POST'])]
    public function UpdateTypeProbleme(#[CurrentUser] User $user,Request $request,TypeProblemeRepository $typeProblemeRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $type = $typeProblemeRepository->find($id);
        $type->setNom($request->request->get('nom'));
        $type->setUpdatedAt(new \DateTimeImmutable());
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER LES TYPES DE PROBLEME
    #[Route('/type/probleme/get', name: 'app_get_type_probleme')]
    public function getTypeProbleme(#[CurrentUser] User $user,ProblemeRepository $problemeRepository): Response
    {
        $type = $problemeRepository->findAll();
        $response = new Response(json_encode( array( 'type' => $type) ) );
        return $response;
    }

    // CREER UNE UN TYPE DE TRANSACTION
    #[Route('/type/transaction/add', name: 'app_add_new_type_transaction', methods: ['POST'])]
    public function AddTypeTransaction(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager): Response
    {
        $type = new TypeTransaction();
        $type->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de type de transaction",
        ]);
    }

    // MODIFIER UN TYPE DE TRANSACTION
    #[Route('/type/transaction/update/{id}', name: 'app_update_type_transaction', methods: ['POST'])]
    public function UpdateTypeTransaction(#[CurrentUser] User $user,Request $request,TypeTransactionRepository $typeTransactionRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $type = $typeTransactionRepository->find($id);
        $type->setNom($request->request->get('nom'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($type);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }

    // AFFICHER LES TYPES DE TRANSACTIONS
    #[Route('/type/transaction/get', name: 'app_get_transaction')]
    public function getTypeTransaction(#[CurrentUser] User $user,TypeTransactionRepository $typeTransactionRepository): Response
    {
        $type = $typeTransactionRepository->findAll();
        $response = new Response(json_encode( array( 'type' => $type) ) );
        return $response;
    }
    // CREER UNE PERIODE
    #[Route('/periode/add', name: 'app_add_new_periode', methods: ['POST'])]
    public function AddPeriode(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager): Response
    {
        $periode = new Periode();
        $periode->setTitre($request->request->get('titre'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($periode);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Cree avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de creation de periode",
        ]);
    }

    // MODIFIER UNE PERIODE
    #[Route('/periode/update', name: 'app_add_update_periode', methods: ['POST'])]
    public function UpdatePeriode(#[CurrentUser] User $user,Request $request,EntityManagerInterface $entityManager,PeriodeRepository $periodeRepository,int $id): Response
    {
        $periode = $periodeRepository->find($id);
        $periode->setTitre($request->request->get('titre'));
        if ($request->getMethod() == Request::METHOD_POST){
            $entityManager->getConnection()->beginTransaction();
            try {
                $entityManager->persist($periode);
                $entityManager->flush();
                $entityManager->commit();
            } catch (\Exception $e) {
                $entityManager->rollback();
                throw $e;
            }

            return $this->json(['message' => 'Modification effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de Modification",
        ]);
    }
    // AFFICHER LES PERIODES
    #[Route('/periode/get', name: 'app_get_periode')]
    public function getPeriode(#[CurrentUser] User $user,PeriodeRepository $periodeRepository): Response
    {
        $periode = $periodeRepository->findAllExceptThis(6);
        $response = new Response(json_encode( array( 'periode' => $periode) ) );
        return $response;
    }
}
