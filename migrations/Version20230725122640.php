<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230725122640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bien_immo ADD nom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE bien_immo ADD vue INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bien_immo ADD chambre INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bien_immo ADD cuisine INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bien_immo ADD toilette INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE bien_immo DROP nom');
        $this->addSql('ALTER TABLE bien_immo DROP vue');
        $this->addSql('ALTER TABLE bien_immo DROP chambre');
        $this->addSql('ALTER TABLE bien_immo DROP cuisine');
        $this->addSql('ALTER TABLE bien_immo DROP toilette');
    }
}
