<?php

namespace App\Controller\Admin;

use App\Entity\PhotoImmo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PhotoImmoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoImmo::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
