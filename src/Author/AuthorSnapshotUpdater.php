<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Author;

use Contao\CoreBundle\Cache\EntityCacheTags;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hält die Kopie von Name und E-Mail-Adresse in tl_ini_idea (authorName,
 * authorEmail) auf dem Stand von tl_member.
 *
 * Angezeigt werden bevorzugt die Daten aus tl_member. Die Kopie greift erst,
 * wenn das Mitglied gelöscht wurde. Geschrieben wird sie beim Einreichen,
 * aktuell gehalten durch den stündlichen AuthorSnapshotCron.
 *
 * Hat sich etwas geändert, wird der Cache-Tag der Ideenliste invalidiert,
 * damit gecachte Seiten den neuen Namen zeigen.
 */
readonly class AuthorSnapshotUpdater
{
    private const SET = "i.authorName = COALESCE(NULLIF(TRIM(CONCAT_WS(' ', m.firstname, m.lastname)), ''), m.username, i.authorName),
                         i.authorEmail = COALESCE(NULLIF(m.email, ''), i.authorEmail)";

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'contao.cache.entity_tags')]
        private EntityCacheTags $entityCacheTags,
    ) {
    }

    /**
     * Gleicht alle Ideen ab. Bewusst ohne Zeitfenster: Die Tabelle ist klein,
     * und so werden auch Änderungen am Backend vorbei erfasst, die tstamp in
     * tl_member nicht setzen.
     *
     * @throws Exception
     */
    public function updateAll(): int
    {
        return $this->invalidateIfChanged($this->connection->executeStatement(
            'UPDATE tl_ini_idea i JOIN tl_member m ON m.id = i.memberId SET '.self::SET.' WHERE i.memberId > 0',
        ));
    }

    /**
     * Ergänzt die Kopie bei Ideen, die noch keine haben (Version 1.0).
     *
     * @throws Exception
     */
    public function fillMissing(): int
    {
        return $this->invalidateIfChanged($this->connection->executeStatement(
            "UPDATE tl_ini_idea i JOIN tl_member m ON m.id = i.memberId SET ".self::SET." WHERE i.memberId > 0 AND i.authorName = ''",
        ));
    }

    /**
     * @throws Exception
     */
    public function hasMissing(): bool
    {
        return (bool) $this->connection->fetchOne(
            "SELECT 1 FROM tl_ini_idea i JOIN tl_member m ON m.id = i.memberId WHERE i.memberId > 0 AND i.authorName = '' LIMIT 1",
        );
    }

    private function invalidateIfChanged(int $count): int
    {
        if ($count > 0) {
            $this->entityCacheTags->invalidateTagsFor('contao.db.tl_ini_idea');
        }

        return $count;
    }
}
