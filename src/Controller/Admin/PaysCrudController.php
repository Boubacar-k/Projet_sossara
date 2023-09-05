<?php

namespace App\Controller\Admin;

use App\Entity\Pays;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PaysCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Pays::class;
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
