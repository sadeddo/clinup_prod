<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231223002045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE logement ADD hote_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_F0FD4457453D3D6F ON logement (hote_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457453D3D6F');
        $this->addSql('DROP INDEX IDX_F0FD4457453D3D6F ON logement');
        $this->addSql('ALTER TABLE logement DROP hote_id');
    }
}
