<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250325231725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDB19EB6921');
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDBBE3DB2B7');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDB19EB6921 FOREIGN KEY (client_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDBBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A02F368D');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B29A9963');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A02F368D FOREIGN KEY (participant2_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B29A9963 FOREIGN KEY (participant1_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE dispo DROP FOREIGN KEY FK_483B4D2FBE3DB2B7');
        $this->addSql('ALTER TABLE dispo ADD CONSTRAINT FK_483B4D2FBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE experience DROP FOREIGN KEY FK_590C103BE3DB2B7');
        $this->addSql('ALTER TABLE experience ADD CONSTRAINT FK_590C103BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE icalres DROP FOREIGN KEY FK_5E73BEA058ABF955');
        $this->addSql('ALTER TABLE icalres CHANGE dt_start dt_start DATETIME DEFAULT NULL, CHANGE dt_end dt_end DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE icalres ADD CONSTRAINT FK_5E73BEA058ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E73BEA0539B0606 ON icalres (uid)');
        $this->addSql('ALTER TABLE img_task DROP FOREIGN KEY FK_9C6F2A1D8DB60186');
        $this->addSql('ALTER TABLE img_task ADD CONSTRAINT FK_9C6F2A1D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338453D3D6F');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338BA2F97F0');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338BA2F97F0 FOREIGN KEY (presta_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE justif DROP FOREIGN KEY FK_1303395DBE3DB2B7');
        $this->addSql('ALTER TABLE justif ADD CONSTRAINT FK_1303395DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457453D3D6F');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457453D3D6F FOREIGN KEY (hote_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E0F2C01E');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E92F8F78');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E0F2C01E FOREIGN KEY (id_conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E92F8F78 FOREIGN KEY (recipient_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notif DROP FOREIGN KEY FK_C0730D6BA76ED395');
        $this->addSql('ALTER TABLE notif ADD CONSTRAINT FK_C0730D6BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DB83297E7');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DBE3DB2B7');
        $this->addSql('ALTER TABLE postuler ADD proposition VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE probleme DROP FOREIGN KEY FK_7AB2D71458ABF955');
        $this->addSql('ALTER TABLE probleme ADD CONSTRAINT FK_7AB2D71458ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE receipt DROP FOREIGN KEY FK_5399B645B83297E7');
        $this->addSql('ALTER TABLE receipt ADD CONSTRAINT FK_5399B645B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955BE3DB2B7');
        $this->addSql('ALTER TABLE reservation DROP grp_pay');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2558ABF955');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id)');
        $this->addSql('ALTER TABLE user ADD siret VARCHAR(255) DEFAULT NULL, ADD raison_sociale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CB83297E7');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDB19EB6921');
        $this->addSql('ALTER TABLE comment_presta DROP FOREIGN KEY FK_D8487BDBBE3DB2B7');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDB19EB6921 FOREIGN KEY (client_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_presta ADD CONSTRAINT FK_D8487BDBBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B29A9963');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9A02F368D');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B29A9963 FOREIGN KEY (participant1_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9A02F368D FOREIGN KEY (participant2_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dispo DROP FOREIGN KEY FK_483B4D2FBE3DB2B7');
        $this->addSql('ALTER TABLE dispo ADD CONSTRAINT FK_483B4D2FBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE experience DROP FOREIGN KEY FK_590C103BE3DB2B7');
        $this->addSql('ALTER TABLE experience ADD CONSTRAINT FK_590C103BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE icalres DROP FOREIGN KEY FK_5E73BEA058ABF955');
        $this->addSql('DROP INDEX UNIQ_5E73BEA0539B0606 ON icalres');
        $this->addSql('ALTER TABLE icalres CHANGE dt_start dt_start VARCHAR(255) DEFAULT NULL, CHANGE dt_end dt_end VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE icalres ADD CONSTRAINT FK_5E73BEA058ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE img_task DROP FOREIGN KEY FK_9C6F2A1D8DB60186');
        $this->addSql('ALTER TABLE img_task ADD CONSTRAINT FK_9C6F2A1D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338453D3D6F');
        $this->addSql('ALTER TABLE invit DROP FOREIGN KEY FK_3AD21338BA2F97F0');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338453D3D6F FOREIGN KEY (hote_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invit ADD CONSTRAINT FK_3AD21338BA2F97F0 FOREIGN KEY (presta_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE justif DROP FOREIGN KEY FK_1303395DBE3DB2B7');
        $this->addSql('ALTER TABLE justif ADD CONSTRAINT FK_1303395DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE logement DROP FOREIGN KEY FK_F0FD4457453D3D6F');
        $this->addSql('ALTER TABLE logement ADD CONSTRAINT FK_F0FD4457453D3D6F FOREIGN KEY (hote_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96F624B39D');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E92F8F78');
        $this->addSql('ALTER TABLE messages DROP FOREIGN KEY FK_DB021E96E0F2C01E');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E96E0F2C01E FOREIGN KEY (id_conversation_id) REFERENCES conversation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notif DROP FOREIGN KEY FK_C0730D6BA76ED395');
        $this->addSql('ALTER TABLE notif ADD CONSTRAINT FK_C0730D6BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DBE3DB2B7');
        $this->addSql('ALTER TABLE postuler DROP FOREIGN KEY FK_8EC5A68DB83297E7');
        $this->addSql('ALTER TABLE postuler DROP proposition');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DBE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postuler ADD CONSTRAINT FK_8EC5A68DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE probleme DROP FOREIGN KEY FK_7AB2D71458ABF955');
        $this->addSql('ALTER TABLE probleme ADD CONSTRAINT FK_7AB2D71458ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE receipt DROP FOREIGN KEY FK_5399B645B83297E7');
        $this->addSql('ALTER TABLE receipt ADD CONSTRAINT FK_5399B645B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495558ABF955');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955BE3DB2B7');
        $this->addSql('ALTER TABLE reservation ADD grp_pay VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955BE3DB2B7 FOREIGN KEY (prestataire_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2558ABF955');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2558ABF955 FOREIGN KEY (logement_id) REFERENCES logement (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` DROP siret, DROP raison_sociale');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CB83297E7');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
