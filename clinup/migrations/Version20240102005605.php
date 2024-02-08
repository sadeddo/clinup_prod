<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240102005605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment_presta (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, prestataire_id INT DEFAULT NULL, comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', evaluation INT DEFAULT NULL, reponse LONGTEXT DEFAULT NULL, date_rep DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_D8487BDB19EB6921 (client_id), INDEX IDX_D8487BDBBE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDB19EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDBBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDB19EB6921');
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDBBE3DB2B7');
        $this->addSql('DROP TABLE comment_presta');
    }
}
