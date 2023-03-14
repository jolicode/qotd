<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230314091336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support for vote';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ADD voter_ids JSON');
        $this->addSql("UPDATE qotd set voter_ids = '[]'");
        $this->addSql('ALTER TABLE qotd ALTER voter_ids SET NOT NULL');

        $this->addSql('ALTER TABLE qotd ADD vote INT');
        $this->addSql('UPDATE qotd set vote = 0');
        $this->addSql('ALTER TABLE qotd ALTER vote SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd DROP voter_ids');
        $this->addSql('ALTER TABLE qotd DROP vote ');
    }
}
