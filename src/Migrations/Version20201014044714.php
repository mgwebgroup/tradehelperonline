<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201014044714 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Watchlist Migration';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE watchlist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_instrument (watchlist_id INT NOT NULL, instrument_id INT NOT NULL, INDEX IDX_4913FA883DD0D94 (watchlist_id), INDEX IDX_4913FA8CF11D9C (instrument_id), PRIMARY KEY(watchlist_id, instrument_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_expression (watchlist_id INT NOT NULL, expression_id INT NOT NULL, INDEX IDX_E01E007483DD0D94 (watchlist_id), INDEX IDX_E01E0074ADBB65A1 (expression_id), PRIMARY KEY(watchlist_id, expression_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA883DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA8CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_expression ADD CONSTRAINT FK_E01E007483DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_expression ADD CONSTRAINT FK_E01E0074ADBB65A1 FOREIGN KEY (expression_id) REFERENCES expression (id) ON DELETE CASCADE');
//        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE exchange exchange VARCHAR(80) DEFAULT NULL');
//        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT NULL');
//        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT NULL, CHANGE high high DOUBLE PRECISION DEFAULT NULL, CHANGE low low DOUBLE PRECISION DEFAULT NULL, CHANGE close close DOUBLE PRECISION DEFAULT NULL, CHANGE volume volume DOUBLE PRECISION DEFAULT NULL, CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT NULL');
//        $this->addSql('ALTER TABLE expression CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE criteria criteria LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE watchlist_instrument DROP FOREIGN KEY FK_4913FA883DD0D94');
        $this->addSql('ALTER TABLE watchlist_expression DROP FOREIGN KEY FK_E01E007483DD0D94');
        $this->addSql('DROP TABLE watchlist');
        $this->addSql('DROP TABLE watchlist_instrument');
        $this->addSql('DROP TABLE watchlist_expression');
//        $this->addSql('ALTER TABLE expression CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE criteria criteria LONGTEXT DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\'');
//        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE exchange exchange VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
//        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
//        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT \'NULL\', CHANGE high high DOUBLE PRECISION DEFAULT \'NULL\', CHANGE low low DOUBLE PRECISION DEFAULT \'NULL\', CHANGE close close DOUBLE PRECISION DEFAULT \'NULL\', CHANGE volume volume DOUBLE PRECISION DEFAULT \'NULL\', CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
