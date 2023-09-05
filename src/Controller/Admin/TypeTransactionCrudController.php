<?php

namespace App\Controller\Admin;

use App\Entity\TypeTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class TypeTransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TypeTransaction::class;
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
