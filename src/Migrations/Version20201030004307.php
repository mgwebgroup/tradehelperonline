<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201030004307 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create Study Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');


        $this->addSql('CREATE TABLE study (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_watchlist (study_id INT NOT NULL, watchlist_id INT NOT NULL, INDEX IDX_A0C43A42E7B003E9 (study_id), INDEX IDX_A0C43A4283DD0D94 (watchlist_id), PRIMARY KEY(study_id, watchlist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE study_watchlist ADD CONSTRAINT FK_A0C43A42E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study_watchlist ADD CONSTRAINT FK_A0C43A4283DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE study_watchlist DROP FOREIGN KEY FK_A0C43A42E7B003E9');
        $this->addSql('DROP TABLE study');
        $this->addSql('DROP TABLE study_watchlist');
    }
}
