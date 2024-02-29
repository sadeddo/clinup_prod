<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240228003420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invit (id INT AUTO_INCREMENT NOT NULL, hote_id INT DEFAULT NULL, presta_id INT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, date VARCHAR(255) DEFAULT NULL, etat VARCHAR(255) DEFAULT NULL, INDEX IDX_3AD21338453D3D6F (hote_id), INDEX IDX_3AD21338BA2F97F0 (presta_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338BA2F97F0 FOREIGN KEY (presta_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338453D3D6F');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338BA2F97F0');
        $this->addSql('DROP TABLE invit');
    }
}
