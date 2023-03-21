<?php

namespace DoctrineMigrations;

use App\Repository\Model\QotdVote;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230321211454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow to vote/unvote a QOTD';
    }

    public function up(Schema $schema): void
    {
        $rows = $this
            ->connection
            ->executeQuery('SELECT id, voter_ids FROM qotd')
            ->fetchAllAssociative()
        ;

        foreach ($rows as $row) {
            $voterIds = json_decode($row['voter_ids'], true);
            $newVoterIds = [];
            foreach ($voterIds as $voterId) {
                $newVoterIds[$voterId] = QotdVote::Unknown->value;
            }

            $this->addSql('UPDATE qotd SET voter_ids = :voterIds WHERE id = :id', [
                'voterIds' => json_encode($newVoterIds),
                'id' => $row['id'],
            ]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
