<?php

namespace App\Controller\Admin;

use App\Entity\PhotoDocument;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PhotoDocumentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoDocument::class;
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
