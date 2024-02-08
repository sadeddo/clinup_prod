<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231225171734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dispo (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, dt_start VARCHAR(255) NOT NULL, dt_end VARCHAR(255) NOT NULL, statut VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, INDEX IDX_483B4D2FBE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dispo ADD CONSTRAINT FK_483B4D2FBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dispo DROP FOREIGN KEY FK_483B4D2FBE3DB2B7');
        $this->addSql('DROP TABLE dispo');
    }
}
