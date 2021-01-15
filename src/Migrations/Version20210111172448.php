<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210111172448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ohlcvhistory (id INT AUTO_INCREMENT NOT NULL, instrument_id INT NOT NULL, open DOUBLE PRECISION NOT NULL, high DOUBLE PRECISION NOT NULL, low DOUBLE PRECISION NOT NULL, close DOUBLE PRECISION NOT NULL, volume BIGINT DEFAULT NULL, timeinterval VARCHAR(255) NOT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, provider VARCHAR(255) DEFAULT NULL, INDEX IDX_B5D82CD6CF11D9C (instrument_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE instruments (id INT AUTO_INCREMENT NOT NULL, symbol VARCHAR(21) NOT NULL, name VARCHAR(80) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, exchange VARCHAR(80) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, calculated_formulas LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_instrument (watchlist_id INT NOT NULL, instrument_id INT NOT NULL, INDEX IDX_4913FA883DD0D94 (watchlist_id), INDEX IDX_4913FA8CF11D9C (instrument_id), PRIMARY KEY(watchlist_id, instrument_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_expression (watchlist_id INT NOT NULL, expression_id INT NOT NULL, INDEX IDX_E01E007483DD0D94 (watchlist_id), INDEX IDX_E01E0074ADBB65A1 (expression_id), PRIMARY KEY(watchlist_id, expression_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE expression (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, timeinterval VARCHAR(255) NOT NULL COMMENT \'(DC2Type:dateinterval)\', name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, formula LONGTEXT NOT NULL, criteria LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ohlcvquote (id INT AUTO_INCREMENT NOT NULL, instrument_id INT NOT NULL, open DOUBLE PRECISION DEFAULT NULL, high DOUBLE PRECISION DEFAULT NULL, low DOUBLE PRECISION DEFAULT NULL, close DOUBLE PRECISION DEFAULT NULL, volume INT DEFAULT NULL, timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, provider VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_69EF258ECF11D9C (instrument_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_text (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL, INDEX IDX_BAB34425E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, version VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_watchlist (study_id INT NOT NULL, watchlist_id INT NOT NULL, INDEX IDX_A0C43A42E7B003E9 (study_id), INDEX IDX_A0C43A4283DD0D94 (watchlist_id), PRIMARY KEY(study_id, watchlist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_array (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_EF895500E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_float (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value DOUBLE PRECISION DEFAULT NULL, INDEX IDX_8720E522E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE study_attribute_json (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, _value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_EA3FC6A7E7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA883DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA8CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_expression ADD CONSTRAINT FK_E01E007483DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_expression ADD CONSTRAINT FK_E01E0074ADBB65A1 FOREIGN KEY (expression_id) REFERENCES expression (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study_attribute_text ADD CONSTRAINT FK_BAB34425E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_watchlist ADD CONSTRAINT FK_A0C43A42E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study_watchlist ADD CONSTRAINT FK_A0C43A4283DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study_attribute_array ADD CONSTRAINT FK_EF895500E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_attribute_float ADD CONSTRAINT FK_8720E522E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
        $this->addSql('ALTER TABLE study_attribute_json ADD CONSTRAINT FK_EA3FC6A7E7B003E9 FOREIGN KEY (study_id) REFERENCES study (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6CF11D9C');
        $this->addSql('ALTER TABLE watchlist_instrument DROP FOREIGN KEY FK_4913FA8CF11D9C');
        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('ALTER TABLE watchlist_instrument DROP FOREIGN KEY FK_4913FA883DD0D94');
        $this->addSql('ALTER TABLE watchlist_expression DROP FOREIGN KEY FK_E01E007483DD0D94');
        $this->addSql('ALTER TABLE study_watchlist DROP FOREIGN KEY FK_A0C43A4283DD0D94');
        $this->addSql('ALTER TABLE watchlist_expression DROP FOREIGN KEY FK_E01E0074ADBB65A1');
        $this->addSql('ALTER TABLE study_attribute_text DROP FOREIGN KEY FK_BAB34425E7B003E9');
        $this->addSql('ALTER TABLE study_watchlist DROP FOREIGN KEY FK_A0C43A42E7B003E9');
        $this->addSql('ALTER TABLE study_attribute_array DROP FOREIGN KEY FK_EF895500E7B003E9');
        $this->addSql('ALTER TABLE study_attribute_float DROP FOREIGN KEY FK_8720E522E7B003E9');
        $this->addSql('ALTER TABLE study_attribute_json DROP FOREIGN KEY FK_EA3FC6A7E7B003E9');
        $this->addSql('DROP TABLE ohlcvhistory');
        $this->addSql('DROP TABLE instruments');
        $this->addSql('DROP TABLE watchlist');
        $this->addSql('DROP TABLE watchlist_instrument');
        $this->addSql('DROP TABLE watchlist_expression');
        $this->addSql('DROP TABLE expression');
        $this->addSql('DROP TABLE ohlcvquote');
        $this->addSql('DROP TABLE study_attribute_text');
        $this->addSql('DROP TABLE study');
        $this->addSql('DROP TABLE study_watchlist');
        $this->addSql('DROP TABLE study_attribute_array');
        $this->addSql('DROP TABLE study_attribute_float');
        $this->addSql('DROP TABLE study_attribute_json');
    }
}
