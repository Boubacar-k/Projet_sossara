<?php

namespace App\Controller\Admin;

use App\Entity\BienImmo;
use App\Entity\Blog;
use App\Entity\Commodite;
use App\Entity\Commune;
use App\Entity\Document;
use App\Entity\Pays;
use App\Entity\PhotoDocument;
use App\Entity\Region;
use App\Entity\TypeImmo;
use App\Entity\TypeProbleme;
use App\Entity\TypeTransaction;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }
    
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $url = $this->adminUrlGenerator->setController(UserCrudController::class)->generateUrl();
        return $this->redirect($url);
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Sossara');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-user', User::class);
        yield MenuItem::linkToCrud('Documents Utilisateurs', 'fas fa-folder-open', Document::class);
        yield MenuItem::linkToCrud('Photos Documents', 'fas fa-image', PhotoDocument::class);
        yield MenuItem::linkToCrud('Biens Immobiliers', 'fas fa-place-of-worship', BienImmo::class);
        yield MenuItem::linkToCrud('Blog', 'fas fa-blog', Blog::class);
        yield MenuItem::linkToCrud('Pays', 'fas fa-flag', Pays::class);
        yield MenuItem::linkToCrud('region', 'fas fa-map', Region::class);
        yield MenuItem::linkToCrud('Commune', 'fas fa-map-location', Commune::class);
        yield MenuItem::linkToCrud('Commodites', 'fas fa-square-check', Commodite::class);
        yield MenuItem::linkToCrud('Type Immobilier', 'fas fa-list', TypeImmo::class);
        yield MenuItem::linkToCrud('Type Probleme', 'fas fa-list', TypeProbleme::class);
        yield MenuItem::linkToCrud('Type Transaction', 'fas fa-list-check', TypeTransaction::class);

    }
}
