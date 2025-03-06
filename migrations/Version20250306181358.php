<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250306181358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change date and updated_at column types';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ALTER date TYPE DATE');
        $this->addSql('ALTER TABLE qotd ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN qotd.date IS \'\'');
        $this->addSql('COMMENT ON COLUMN qotd.updated_at IS \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE qotd ALTER date TYPE DATE');
        $this->addSql('COMMENT ON COLUMN qotd.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN qotd.date IS \'(DC2Type:date_immutable)\'');
    }
}
