<?php

namespace App\Controller;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/api/home/index', name: 'api_app_home_index')]
    public function index(): Response
    {
        $email = $this->getUser()->getEmail();
        $token = (new Builder)
        ->withClaim('mercure', ['subscribe' => [sprintf("/%s",$email)]])
        ->getToken(
            new Sha256(),
            new Key($this->getParameter('secret'))
        )
        ;
        $response = $this->render('index/index.html.twig',['controller_name' => 'IndexController']);

        $response->headers->setCookie(
            new Cookie(
                'mercureAuthorizarion',
                $token,
                (new \DateTime())
                ->add(new \DateInterval('PT2H')),
                '/.well-known/mercure',
                null,
                false,
                true,
                false,
                'strict'
            )
        );

        return $response;
    }
}
