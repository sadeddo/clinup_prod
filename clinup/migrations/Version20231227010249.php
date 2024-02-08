<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231227010249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE postuler (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, reservation_id INT DEFAULT NULL, comment LONGTEXT NOT NULL, INDEX IDX_8EC5A68DBE3DB2B7 (prestataire_id), INDEX IDX_8EC5A68DB83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DBE3DB2B7');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DB83297E7');
        $this->addSql('DROP TABLE postuler');
    }
}
