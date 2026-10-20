<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener\DataContainer;

use BohnMedia\ContaoIdeasBundle\NotificationCenter\IniIdeaSubmittedNotificationType;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * Liefert die Benachrichtigungen vom Typ "Idee eingereicht" für das
 * Inhaltselement "Idee einreichen".
 */
#[AsCallback(table: 'tl_content', target: 'fields.iniIdeaNotification.options')]
readonly class IniIdeaNotificationOptionsListener
{
    public function __construct(private NotificationCenter $notificationCenter)
    {
    }

    /**
     * @return array<int, string>
     */
    public function __invoke(): array
    {
        return $this->notificationCenter->getNotificationsForNotificationType(IniIdeaSubmittedNotificationType::NAME);
    }
}
