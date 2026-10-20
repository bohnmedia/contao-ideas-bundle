<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendUser;
use Contao\Form;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Ergänzt die über "Eingaben speichern" in tl_ini_idea geschriebenen
 * Formulardaten um die Felder, die nicht aus dem Formular selbst kommen.
 */
readonly class StoreIniIdeaListener
{
    public const TARGET_TABLE = 'tl_ini_idea';

    /**
     * Felder, die aus dem Formular übernommen werden dürfen. Alle übrigen
     * Formularfelder (z. B. Captcha) würden sonst zu einem SQL-Fehler führen.
     */
    private const ALLOWED_FIELDS = ['title', 'description'];

    public function __construct(private  Security $security)
    {
    }

    /**
     * Reichert die übermittelten Daten vor E-Mail-/Notification-Versand um die
     * Mitgliedsdaten an. Läuft vor storeFormData; die Whitelist dort hält die
     * zusätzlichen Schlüssel wieder aus dem INSERT heraus.
     *
     * Im Notification Center stehen die Werte damit als ##form_member_firstname##,
     * ##form_member_lastname## und ##form_member_email## zur Verfügung.
     */
    #[AsHook('prepareFormData')]
    public function onPrepareFormData(array &$submittedData, array $labels, array $fields, Form $form): void
    {
        if (self::TARGET_TABLE !== $form->targetTable) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof FrontendUser) {
            return;
        }

        $submittedData['member_firstname'] = (string) $user->firstname;
        $submittedData['member_lastname'] = (string) $user->lastname;
        $submittedData['member_email'] = (string) $user->email;
    }

    #[AsHook('storeFormData')]
    public function __invoke(array $set, Form $form): array
    {
        if (self::TARGET_TABLE !== $form->targetTable) {
            return $set;
        }

        $set = array_intersect_key($set, array_flip(self::ALLOWED_FIELDS));

        $time = time();

        $set['tstamp'] = $time;
        $set['createdAt'] = $time;
        $set['memberId'] = $this->getMemberId();
        $set['invisible'] = '1';

        return $set;
    }

    private function getMemberId(): int
    {
        $user = $this->security->getUser();

        return $user instanceof FrontendUser ? (int) $user->id : 0;
    }
}
