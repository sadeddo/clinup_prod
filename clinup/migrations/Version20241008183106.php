<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008183106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment_presta (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, prestataire_id INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', evaluation INT DEFAULT NULL, reponse LONGTEXT DEFAULT NULL, date_rep DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', recommandation VARCHAR(255) DEFAULT NULL, INDEX IDX_D8487BDB19EB6921 (client_id), INDEX IDX_D8487BDBBE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, participant1_id INT DEFAULT NULL, participant2_id INT DEFAULT NULL, INDEX IDX_8A8E26E9B29A9963 (participant1_id), INDEX IDX_8A8E26E9A02F368D (participant2_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dispo (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, dt_start VARCHAR(255) NOT NULL, dt_end VARCHAR(255) NOT NULL, statut VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, INDEX IDX_483B4D2FBE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE experience (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, experience VARCHAR(255) NOT NULL, dt_start DATE DEFAULT NULL, dt_end DATE DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_590C103BE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE icalres (id INT AUTO_INCREMENT NOT NULL, logement_id INT DEFAULT NULL, dt_start VARCHAR(255) DEFAULT NULL, dt_end VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, nbr_heure VARCHAR(255) DEFAULT NULL, prix VARCHAR(255) DEFAULT NULL, INDEX IDX_5E73BEA058ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE img_task (id INT AUTO_INCREMENT NOT NULL, task_id INT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, INDEX IDX_9C6F2A1D8DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invit (id INT AUTO_INCREMENT NOT NULL, hote_id INT DEFAULT NULL, presta_id INT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, date VARCHAR(255) DEFAULT NULL, etat VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, message LONGTEXT DEFAULT NULL, INDEX IDX_3AD21338453D3D6F (hote_id), INDEX IDX_3AD21338BA2F97F0 (presta_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE justif (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_1303395DBE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE logement (id INT AUTO_INCREMENT NOT NULL, hote_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(350) NOT NULL, complet_adresse VARCHAR(350) DEFAULT NULL, surface VARCHAR(255) NOT NULL, nbr_chambre INT NOT NULL, nbr_bain INT NOT NULL, description LONGTEXT DEFAULT NULL, airbnb VARCHAR(255) DEFAULT NULL, img VARCHAR(255) DEFAULT NULL, acces VARCHAR(255) DEFAULT NULL, booking VARCHAR(255) DEFAULT NULL, INDEX IDX_F0FD4457453D3D6F (hote_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messages (id INT AUTO_INCREMENT NOT NULL, sender_id INT DEFAULT NULL, recipient_id INT DEFAULT NULL, id_conversation_id INT DEFAULT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(255) DEFAULT NULL, INDEX IDX_DB021E96F624B39D (sender_id), INDEX IDX_DB021E96E92F8F78 (recipient_id), INDEX IDX_DB021E96E0F2C01E (id_conversation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notif (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, content LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', target_url VARCHAR(255) DEFAULT NULL, INDEX IDX_C0730D6BA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE postuler (id INT AUTO_INCREMENT NOT NULL, prestataire_id INT DEFAULT NULL, reservation_id INT DEFAULT NULL, comment LONGTEXT NOT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_8EC5A68DBE3DB2B7 (prestataire_id), INDEX IDX_8EC5A68DB83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE probleme (id INT AUTO_INCREMENT NOT NULL, logement_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, num VARCHAR(255) DEFAULT NULL, criticiter VARCHAR(255) DEFAULT NULL, plan VARCHAR(255) DEFAULT NULL, INDEX IDX_7AB2D71458ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE receipt (id INT AUTO_INCREMENT NOT NULL, reservation_id INT DEFAULT NULL, number VARCHAR(255) DEFAULT NULL, payment_date VARCHAR(255) DEFAULT NULL, INDEX IDX_5399B645B83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, logement_id INT DEFAULT NULL, prestataire_id INT DEFAULT NULL, date DATE NOT NULL, heure TIME NOT NULL, nbr_heure VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, id_intent VARCHAR(255) DEFAULT NULL, prix VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_42C8495558ABF955 (logement_id), INDEX IDX_42C84955BE3DB2B7 (prestataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, subscription_id VARCHAR(255) DEFAULT NULL, dt_start DATE DEFAULT NULL, dt_end DATE DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, ammount VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, id_prod_stripe VARCHAR(255) DEFAULT NULL, subscription_item_id VARCHAR(255) DEFAULT NULL, INDEX IDX_A3C664D3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, logement_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut TINYINT(1) DEFAULT NULL, img VARCHAR(255) DEFAULT NULL, detail VARCHAR(255) DEFAULT NULL, INDEX IDX_527EDB2558ABF955 (logement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, birthday DATE DEFAULT NULL, gender VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(255) DEFAULT NULL, adresse VARCHAR(350) DEFAULT NULL, description LONGTEXT DEFAULT NULL, picture VARCHAR(255) DEFAULT NULL, prix VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) DEFAULT NULL, id_stripe VARCHAR(255) DEFAULT NULL, statut_stripe TINYINT(1) DEFAULT NULL, very_email TINYINT(1) DEFAULT NULL, palier VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE video (id INT AUTO_INCREMENT NOT NULL, reservation_id INT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, INDEX IDX_7CC7DA2CB83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDB19EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDBBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B29A9963 FOREIGN KEY (participant1_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A02F368D FOREIGN KEY (participant2_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE dispo ADD CONSTRAINT FK_483B4D2FBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE experience ADD CONSTRAINT FK_590C103BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE icalres ADD CONSTRAINT FK_5E73BEA058ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE img_task ADD CONSTRAINT FK_9C6F2A1D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338BA2F97F0 FOREIGN KEY (presta_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE justif ADD CONSTRAINT FK_1303395DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E0F2C01E FOREIGN KEY (id_conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE notif ADD CONSTRAINT FK_C0730D6BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE probleme ADD CONSTRAINT FK_7AB2D71458ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE receipt ADD CONSTRAINT FK_5399B645B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDB19EB6921');
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDBBE3DB2B7');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B29A9963');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A02F368D');
        $this->addSql('ALTER TABLE dispo DROP FOREIGN KEY FK_483B4D2FBE3DB2B7');
        $this->addSql('ALTER TABLE experience DROP FOREIGN KEY FK_590C103BE3DB2B7');
        $this->addSql('ALTER TABLE icalres DROP FOREIGN KEY FK_5E73BEA058ABF955');
        $this->addSql('ALTER TABLE img_task DROP FOREIGN KEY FK_9C6F2A1D8DB60186');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338453D3D6F');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338BA2F97F0');
        $this->addSql('ALTER TABLE justif DROP FOREIGN KEY FK_1303395DBE3DB2B7');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457453D3D6F');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E92F8F78');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E0F2C01E');
        $this->addSql('ALTER TABLE notif DROP FOREIGN KEY FK_C0730D6BA76ED395');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DBE3DB2B7');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DB83297E7');
        $this->addSql('ALTER TABLE probleme DROP FOREIGN KEY FK_7AB2D71458ABF955');
        $this->addSql('ALTER TABLE receipt DROP FOREIGN KEY FK_5399B645B83297E7');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955BE3DB2B7');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2558ABF955');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CB83297E7');
        $this->addSql('DROP TABLE comment_presta');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE dispo');
        $this->addSql('DROP TABLE experience');
        $this->addSql('DROP TABLE icalres');
        $this->addSql('DROP TABLE img_task');
        $this->addSql('DROP TABLE invit');
        $this->addSql('DROP TABLE justif');
        $this->addSql('DROP TABLE logement');
        $this->addSql('DROP TABLE messages');
        $this->addSql('DROP TABLE notif');
        $this->addSql('DROP TABLE postuler');
        $this->addSql('DROP TABLE probleme');
        $this->addSql('DROP TABLE receipt');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE video');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
