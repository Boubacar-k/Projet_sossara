<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api', name: 'api_')]
class AngularRedirectController extends AbstractController
{
    #[Route('/angular/redirect', name: 'app_angular_redirect')]
    public function redirectToAngular(Request $request): RedirectResponse
    {
        $angularUrl = 'http://localhost:4200/auth/connexion'; // Remplacez par l'URL de votre page Angular
        return $this->redirect($angularUrl);
    }

    #[Route('/angular/redirect_to reset', name: 'app_angular_redirect_reset')]
    public function redirectToAngularReset(Request $request): RedirectResponse
    {
        $token = $request->get('token');
        $angularUrl = 'http://localhost:4200/reset-password/'.$token; // Remplacez par l'URL de votre page Angular
        return $this->redirect($angularUrl);
    }
}
