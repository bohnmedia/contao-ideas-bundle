<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\NotificationCenter;

use Terminal42\NotificationCenterBundle\NotificationType\NotificationTypeInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\EmailTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;

/**
 * Benachrichtigungstyp "Idee eingereicht".
 *
 * Wird vom Inhaltselement "Idee einreichen" versendet, z. B. an die
 * Redaktion als Hinweis auf eine neue Idee oder als Eingangsbestätigung an
 * den Einreicher.
 */
readonly class IniIdeaSubmittedNotificationType implements NotificationTypeInterface
{
    public const NAME = 'ini_idea_submitted';

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
            $this->factory->create(EmailTokenDefinition::class, 'author_email', 'ini_idea.author_email'),
            $this->factory->create(TextTokenDefinition::class, 'author_name', 'ini_idea.author_name'),
            $this->factory->create(TextTokenDefinition::class, 'is_member', 'ini_idea.is_member'),
            $this->factory->create(TextTokenDefinition::class, 'idea_title', 'ini_idea.idea_title'),
            $this->factory->create(TextTokenDefinition::class, 'idea_description', 'ini_idea.idea_description'),
            $this->factory->create(TextTokenDefinition::class, 'idea_created_at', 'ini_idea.idea_created_at'),
            $this->factory->create(TextTokenDefinition::class, 'idea_id', 'ini_idea.idea_id'),
            $this->factory->create(TextTokenDefinition::class, 'idea_backend_url', 'ini_idea.idea_backend_url'),
        ];
    }
}
