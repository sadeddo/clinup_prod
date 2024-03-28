<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240328000224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE icalres (id INT AUTO_INCREMENT NOT NULL, logement_id INT DEFAULT NULL, dt_start VARCHAR(255) DEFAULT NULL, dt_end VARCHAR(255) DEFAULT NULL, INDEX IDX_5E73BEA058ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE icalres ADD CONSTRAINT FK_5E73BEA058ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE icalres DROP FOREIGN KEY FK_5E73BEA058ABF955');
        $this->addSql('DROP TABLE icalres');
    }
}
