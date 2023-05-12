<?php

namespace App\Doctrine\EventListener;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Events;

/**
 * Some schema/index have been created with a manually crafted migration. This
 * listener prevents migrations from wanting to remove the field and index.
 */
class SchemaListener implements EventSubscriberInterface
{
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $eventArgs): void
    {
        if ('qotd' === $eventArgs->getTable()) {
            if ('message_ts' === $eventArgs->getTableColumn()['field']) {
                $eventArgs->preventDefault();
            }
        }
    }

    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $eventArgs): void
    {
        if ('qotd' === $eventArgs->getTable()
            && \in_array($eventArgs->getTableIndex()['name'], ['qotd_message_trigram'   , 'qotd_message_ts'], true)
        ) {
            $eventArgs->preventDefault();
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onSchemaColumnDefinition,
            Events::onSchemaIndexDefinition,
        ];
    }
}
