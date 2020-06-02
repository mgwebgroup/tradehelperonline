<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200602202640 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE instrument_list_expression (instrument_list_id INT NOT NULL, expression_id INT NOT NULL, INDEX IDX_3596BB00459D692A (instrument_list_id), INDEX IDX_3596BB00ADBB65A1 (expression_id), PRIMARY KEY(instrument_list_id, expression_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expression (id INT AUTO_INCREMENT NOT NULL, expression VARCHAR(2048) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instrument_list_expression ADD CONSTRAINT FK_3596BB00459D692A FOREIGN KEY (instrument_list_id) REFERENCES instrument_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE instrument_list_expression ADD CONSTRAINT FK_3596BB00ADBB65A1 FOREIGN KEY (expression_id) REFERENCES expression (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE exchange exchange VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT NULL, CHANGE high high DOUBLE PRECISION DEFAULT NULL, CHANGE low low DOUBLE PRECISION DEFAULT NULL, CHANGE close close DOUBLE PRECISION DEFAULT NULL, CHANGE volume volume DOUBLE PRECISION DEFAULT NULL, CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE instrument_list CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE instrument_list_expression DROP FOREIGN KEY FK_3596BB00ADBB65A1');
        $this->addSql('DROP TABLE instrument_list_expression');
        $this->addSql('DROP TABLE expression');
        $this->addSql('ALTER TABLE instrument_list CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE exchange exchange VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT \'NULL\', CHANGE high high DOUBLE PRECISION DEFAULT \'NULL\', CHANGE low low DOUBLE PRECISION DEFAULT \'NULL\', CHANGE close close DOUBLE PRECISION DEFAULT \'NULL\', CHANGE volume volume DOUBLE PRECISION DEFAULT \'NULL\', CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
