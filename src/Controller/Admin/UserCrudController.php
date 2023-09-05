<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public const USER_BASE_PATH = 'uploads/images';
    public const USER_UPLOAD_DIR = 'public/uploads/images';

    public function __construct(
        public UserPasswordHasherInterface $userPasswordHasher,
        public FileUploader $fileUploader
    ) {}
    
    public static function getEntityFqcn(): string
    {
        return User::class;
    }
    

    public function configureFields(string $pageName): iterable
    {
        $pass = TextField::new('password')
        ->setFormType(PasswordType::class)
        ->setRequired($pageName === Crud::PAGE_NEW)
        ->onlyOnForms();
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            EmailField::new('email'),
            $pass,
            ImageField::new('photo')->setBasePath(self::USER_BASE_PATH)->setUploadDir(self::USER_UPLOAD_DIR),
            BooleanField::new('isVerified'),
            DateTimeField::new('dateNaissance'),
            TelephoneField::new('telephone'),
            BooleanField::new('is_certified'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updateAt')->hideOnForm(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if(!$entityInstance instanceof User) return;

        $this->encodePassword($entityInstance);
        $entityInstance->setCreatedAt(new \DateTimeImmutable());
        $entityInstance->setUpdateAt(new \DateTimeImmutable());

        parent::persistEntity($entityManager,$entityInstance);
    }

    private function encodePassword(User $user)
    {
        if ($user->getPassword() !== null) {
            
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPassword()));
        }
    }
}
