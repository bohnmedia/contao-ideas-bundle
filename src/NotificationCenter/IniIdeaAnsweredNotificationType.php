<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\NotificationCenter;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

/**
 * Benachrichtigungstyp "Idee beantwortet".
 *
 * Wird über das Interface automatisch beim Notification Center registriert und
 * erscheint dadurch im Backend in der Auswahl "Benachrichtigungstyp".
 */
readonly class IniIdeaAnsweredNotificationType implements NotificationTypeInterface
{
    public const NAME = 'ini_idea_answered';

    public function __construct(private TokenDefinitionFactoryInterface $factory)
    {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getTokenDefinitions(): array
    {
        return [
            $this->factory->create(EmailTokenDefinition::class, 'recipient_email', 'ini_idea.recipient_email'),
            $this->factory->create(TextTokenDefinition::class, 'member_firstname', 'ini_idea.member_firstname'),
            $this->factory->create(TextTokenDefinition::class, 'member_lastname', 'ini_idea.member_lastname'),
            $this->factory->create(TextTokenDefinition::class, 'author_name', 'ini_idea.author_name'),
            $this->factory->create(TextTokenDefinition::class, 'idea_title', 'ini_idea.idea_title'),
            $this->factory->create(TextTokenDefinition::class, 'idea_description', 'ini_idea.idea_description'),
            $this->factory->create(HtmlTokenDefinition::class, 'idea_answer', 'ini_idea.idea_answer'),
            $this->factory->create(TextTokenDefinition::class, 'idea_created_at', 'ini_idea.idea_created_at'),
            $this->factory->create(TextTokenDefinition::class, 'idea_id', 'ini_idea.idea_id'),
        ];
    }
}
