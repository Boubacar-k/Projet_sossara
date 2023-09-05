<?php

namespace App\Controller\Admin;

use App\Entity\TypeProbleme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TypeProblemeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TypeProbleme::class;
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
