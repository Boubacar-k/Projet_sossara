<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted as AttributeIsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Service\FileUploader;
use App\Entity\Blog;
use App\Entity\User;


#[Route('/api', name: 'api_')]
class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog',methods: ['GET'])]
    public function index(BlogRepository $blogRepository): Response
    {
        $blogList = $blogRepository->findAll();
        $response = new Response( json_encode( array( 'blogs' => $blogList ) ) );
        return $response;
    }

    #[Route('/blog/{id}', name: 'app_blog_detail',methods: ['GET'])]
    public function blogDetail(BlogRepository $blogRepository,int $id): Response
    {
        $blogList = $blogRepository->find($id);
        $response = new Response( json_encode( array( 'blogs' => $blogList ) ) );
        return $response;
    }

    // CREER UN BLOG
    #[Route('/blog/add', name: 'app_add_new_blog', methods: ['POST'])]
    #[AttributeIsGranted('ROLE_ADMIN')]
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
    #[AttributeIsGranted('ROLE_ADMIN')]
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

            return $this->json(['message' => 'Publication effectue avec succes'], Response::HTTP_OK);
        }
        return $this->json([
            'erreur' => "erreur de publication",
        ]);
    }


    // SUPPRIMER UN BLOG
    #[Route('/blog/delete/{id}', name: 'app_delete_blog', methods: ['POST'])]
    #[AttributeIsGranted('ROLE_ADMIN')]
    public function deleteBlog(#[CurrentUser] User $user,BlogRepository $blogRepository,EntityManagerInterface $entityManager,int $id): Response
    {
        $blog = $blogRepository->findOneBy(['id' => $id,'utilisateur' => $user]);

        $entityManager->remove($blog);
        $entityManager->flush();

        return $this->json(['message' => 'Suppression effectue succès'], Response::HTTP_OK);
    }
}
