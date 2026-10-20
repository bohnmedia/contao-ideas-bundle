<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener\DataContainer;

use BohnMedia\ContaoIdeasBundle\NotificationCenter\IniIdeaAnsweredNotificationType;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * Benachrichtigt den Fragensteller, sobald seine Idee beantwortet wurde.
 *
 * Versendet werden alle Benachrichtigungen vom Typ "Idee beantwortet" —
 * es muss also nur eine Notification dieses Typs gepflegt werden, eine
 * Zuordnung im Datensatz ist nicht nötig. answerNotifiedAt stellt sicher,
 * dass pro Idee nur einmal versendet wird; das Feld leeren erlaubt einen
 * erneuten Versand.
 */
#[AsCallback(table: 'tl_ini_idea', target: 'config.onsubmit')]
readonly class IniIdeaAnswerNotificationListener
{
    public function __construct(
        private Connection $connection,
        private NotificationCenter $notificationCenter,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT i.id, i.title, i.description, i.answer, i.createdAt, i.answerNotifiedAt,
                    m.firstname, m.lastname, m.email
             FROM tl_ini_idea i
             LEFT JOIN tl_member m ON m.id = i.memberId
             WHERE i.id = ?',
            [$dc->id],
        );

        if (
            false === $row
            || '' === trim((string) $row['answer'])
            || null !== $row['answerNotifiedAt']
            || '' === trim((string) $row['email'])
        ) {
            return;
        }

        $notificationIds = array_keys(
            $this->notificationCenter->getNotificationsForNotificationType(IniIdeaAnsweredNotificationType::NAME),
        );

        // Ohne konfigurierte Notification nichts markieren: sobald eine
        // angelegt ist, versendet das nächste Speichern.
        if ([] === $notificationIds) {
            return;
        }

        $tokens = [
            'recipient_email' => (string) $row['email'],
            'member_firstname' => (string) $row['firstname'],
            'member_lastname' => (string) $row['lastname'],
            'idea_title' => (string) $row['title'],
            'idea_description' => (string) $row['description'],
            'idea_answer' => (string) $row['answer'],
            'idea_created_at' => Date::parse(Config::get('datimFormat'), (int) $row['createdAt']),
            'idea_id' => (string) $row['id'],
        ];

        foreach ($notificationIds as $notificationId) {
            $this->notificationCenter->sendNotification((int) $notificationId, $tokens);
        }

        $this->connection->update(
            'tl_ini_idea',
            ['answerNotifiedAt' => time()],
            ['id' => $dc->id],
        );
    }
}
