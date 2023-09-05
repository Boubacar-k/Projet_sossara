<?php

namespace App\Controller\Admin;

use App\Entity\Commodite;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CommoditeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commodite::class;
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
