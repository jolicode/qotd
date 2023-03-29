<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230329103731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add qotd.message_ts (tsvector) column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION pg_trgm');

        $this->addSql(<<<'EOSQL'
                ALTER TABLE qotd ADD COLUMN message_ts tsvector GENERATED ALWAYS AS (
                    setweight(to_tsvector('french', message), 'A') ||
                    setweight(to_tsvector('english', message), 'B') ||
                    setweight(to_tsvector('simple', regexp_replace(message, '[^\w]',' ', 'g')), 'D')
                ) STORED
            EOSQL);
        $this->addSql('CREATE INDEX qotd_message_ts ON qotd USING GIN (message_ts)');

        $this->addSql('CREATE INDEX qotd_message_trigram ON qotd USING GIN (message gin_trgm_ops);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX qotd_message_ts');
        $this->addSql('DROP INDEX qotd_message_trigram');
        $this->addSql('ALTER TABLE qotd DROP COLUMN message_ts');
        $this->addSql('DROP EXTENSION pg_trgm');
    }
}
