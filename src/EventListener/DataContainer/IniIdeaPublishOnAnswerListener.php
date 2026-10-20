<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Gibt eine Idee automatisch frei, wenn sie frisch beantwortet wird.
 *
 * Entscheidend ist der Übergang der Antwort von leer auf gefüllt in genau
 * diesem Speichervorgang. War die Antwort vorher schon vorhanden, bleibt
 * "invisible" unangetastet — eine beantwortete Idee lässt sich also manuell
 * wieder ausblenden, ohne dass das nächste Speichern sie erneut freischaltet.
 */
class IniIdeaPublishOnAnswerListener
{
    /**
     * @var array<int, true>
     */
    private array $freshlyAnswered = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Merkt sich beim Speichern des Antwortfeldes, ob die Antwort neu ist.
     * Im save_callback enthält $dc->activeRecord noch die alten Werte.
     */
    #[AsCallback(table: 'tl_ini_idea', target: 'fields.answer.save')]
    public function onSaveAnswer(mixed $value, DataContainer $dc): mixed
    {
        $old = trim((string) ($dc->activeRecord->answer ?? ''));

        if ('' === $old && '' !== trim((string) $value)) {
            $this->freshlyAnswered[(int) $dc->id] = true;
        }

        return $value;
    }

    /**
     * Schaltet erst nach dem Speichern aller Felder frei, damit der Wert der
     * Checkbox aus demselben Submit nicht wieder überschrieben wird.
     *
     * @throws Exception
     */
    #[AsCallback(table: 'tl_ini_idea', target: 'config.onsubmit')]
    public function onSubmit(DataContainer $dc): void
    {
        if (!isset($this->freshlyAnswered[(int) $dc->id])) {
            return;
        }

        unset($this->freshlyAnswered[(int) $dc->id]);

        $this->connection->update(
            'tl_ini_idea',
            ['invisible' => 0, 'tstamp' => time()],
            ['id' => (int) $dc->id],
        );
    }
}
