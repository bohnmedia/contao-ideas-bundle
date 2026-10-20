<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platzhalter für die Backend-Vorschau, wie bei Formularen und Modulen im Core:
 * Überschrift, grauer Typname und ein Link zur Verwaltung der Ideen.
 */
trait BackendWildcardTrait
{
    private function getBackendWildcard(ContentModel $model): Response
    {
        return $this->render('@Contao/backend/ini_idea_wildcard.html.twig', [
            'type' => $model->type,
            'title' => StringUtil::deserialize($model->headline, true)['value'] ?? null,
        ]);
    }
}
