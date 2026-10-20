<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Author;

use Contao\FrontendUser;

/**
 * Anzeigename eines Mitglieds: Vor- und Nachname, sonst der Benutzername.
 */
final class MemberName
{
    public static function of(FrontendUser $user): string
    {
        return trim($user->firstname.' '.$user->lastname) ?: (string) $user->username;
    }
}
