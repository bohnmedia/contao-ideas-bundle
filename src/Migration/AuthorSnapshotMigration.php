<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Migration;

use BohnMedia\ContaoIdeasBundle\Author\AuthorSnapshotUpdater;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Überträgt bei Ideen aus Version 1.0 Name und E-Mail-Adresse des Mitglieds
 * nach authorName und authorEmail, damit sie auch nach dem Löschen des
 * Mitglieds erhalten bleiben. Für bereits gelöschte Mitglieder ist das nicht
 * mehr möglich, diese Ideen behalten den Platzhalter.
 */
class AuthorSnapshotMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AuthorSnapshotUpdater $updater,
    ) {
    }

    /**
     * @throws Exception
     */
    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Die Spalten legt erst das Schema-Update an, contao:migrate ruft die
        // Migration danach erneut auf
        if (!$schemaManager->tablesExist(['tl_ini_idea', 'tl_member'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_ini_idea');

        if (!isset($columns['authorname'], $columns['authoremail'])) {
            return false;
        }

        return $this->updater->hasMissing();
    }

    /**
     * @throws Exception
     */
    public function run(): MigrationResult
    {
        $count = $this->updater->fillMissing();

        return $this->createResult(true, \sprintf('Autor bei %d Ideen aus tl_member übernommen.', $count));
    }
}
