<?php

namespace App\Controller\Admin;

use App\Entity\TypeImmo;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TypeImmoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TypeImmo::class;
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
