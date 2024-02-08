<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231226181916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE experience (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, experience VARCHAR(255) NOT NULL, dt_start DATE DEFAULT NULL, dt_end DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_590C103BE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE experience ADD CONSTRAINT FK_590C103BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user CHANGE birthday birthday DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE experience DROP FOREIGN KEY FK_590C103BE3DB2B7');
        $this->addSql('DROP TABLE experience');
        $this->addSql('ALTER TABLE `user` CHANGE birthday birthday VARCHAR(255) DEFAULT NULL');
    }
}
