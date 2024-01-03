<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240103154351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add context to QOTD';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ADD context TEXT');
        $this->addSql('UPDATE qotd SET context = \'\'');
        $this->addSql('ALTER TABLE qotd ALTER context SET NOT NULL');
        $this->addSql('ALTER TABLE qotd ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN qotd.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE qotd SET updated_at = NOW()');
        $this->addSql('ALTER TABLE qotd ALTER updated_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd DROP context');
        $this->addSql('ALTER TABLE qotd DROP updated_at');
    }
}
