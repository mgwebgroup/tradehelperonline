<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201030040804 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE study_attribute_text (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL, INDEX IDX_BAB34425E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_array (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_EF895500E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_float (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value DOUBLE PRECISION DEFAULT NULL, INDEX IDX_8720E522E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_json (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_EA3FC6A7E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE study_attribute_text ADD CONSTRAINT FK_BAB34425E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_attribute_array ADD CONSTRAINT FK_EF895500E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_attribute_float ADD CONSTRAINT FK_8720E522E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_attribute_json ADD CONSTRAINT FK_EA3FC6A7E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE study_attribute_text');
        $this->addSql('DROP TABLE study_attribute_array');
        $this->addSql('DROP TABLE study_attribute_float');
        $this->addSql('DROP TABLE study_attribute_json');
    }
}
