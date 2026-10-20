<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

/**
 * Setzt in der Backend-Liste den Autor ein: Mitgliedsname, sonst den bei der
 * Einreichung ohne Login eingetragenen Namen, sonst einen Platzhalter.
 */
#[AsCallback(table: 'tl_ini_idea', target: 'list.label.label')]
class IniIdeaLabelListener
{
    /**
     * @param array<string, mixed> $row
     * @param array<int, mixed>    $args Werte zu list.label.fields (title, memberId, createdAt)
     */
    public function __invoke(array $row, string $label, DataContainer $dc, array $args): string
    {
        // foreignKey liefert für Gäste und gelöschte Mitglieder einen Leerstring
        $author = trim((string) ($args[1] ?? '')) ?: trim((string) ($row['authorName'] ?? ''));

        if ('' === $author) {
            $author = (int) $row['memberId'] > 0
                ? $GLOBALS['TL_LANG']['MSC']['iniIdea']['formerMember']
                : $GLOBALS['TL_LANG']['MSC']['iniIdea']['anonymous'];
        }

        $format = $GLOBALS['TL_DCA']['tl_ini_idea']['list']['label']['format'] ?? '%s [%s, %s]';

        return \sprintf($format, $args[0] ?? '', $author, $args[2] ?? '');
    }
}
