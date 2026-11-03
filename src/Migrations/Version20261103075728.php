<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103075728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) NOT NULL, payment_status VARCHAR(50) NOT NULL, paid_at DATETIME NOT NULL, reservation_id INT NOT NULL, UNIQUE INDEX UNIQ_6D28840DB83297E7 (reservation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, check_in DATE NOT NULL, check_out DATE NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(50) NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_42C8495554177093 (room_id), INDEX IDX_42C84955A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_794381C654177093 (room_id), INDEX IDX_794381C6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495554177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C654177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DB83297E7');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495554177093');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C654177093');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6A76ED395');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE review');
    }
}
