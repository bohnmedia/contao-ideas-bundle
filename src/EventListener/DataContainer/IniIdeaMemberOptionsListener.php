<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Liefert die Auswahlliste der Mitglieder für tl_ini_idea.memberId.
 */
#[AsCallback(table: 'tl_ini_idea', target: 'fields.memberId.options')]
readonly class IniIdeaMemberOptionsListener
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return array<int, string>
     *
     * @throws Exception
     */
    public function __invoke(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, CONCAT(firstname, ' ', lastname) AS name, username
             FROM tl_member
             ORDER BY lastname, firstname",
        );

        $options = [];

        foreach ($rows as $row) {
            $name = trim((string) $row['name']);

            $options[(int) $row['id']] = ('' !== $name ? $name : (string) $row['username']).' (ID '.$row['id'].')';
        }

        return $options;
    }
}
