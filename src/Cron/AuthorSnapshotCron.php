<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Cron;

use BohnMedia\ContaoIdeasBundle\Author\AuthorSnapshotUpdater;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Doctrine\DBAL\Exception;

/**
 * Gleicht stündlich die Kopie von Name und E-Mail-Adresse in tl_ini_idea mit
 * tl_member ab, damit nach dem Löschen eines Mitglieds dessen zuletzt
 * bekannter Name angezeigt wird.
 */
#[AsCronJob('hourly')]
readonly class AuthorSnapshotCron
{
    public function __construct(private AuthorSnapshotUpdater $updater)
    {
    }

    /**
     * @throws Exception
     */
    public function __invoke(): void
    {
        $this->updater->updateAll();
    }
}
