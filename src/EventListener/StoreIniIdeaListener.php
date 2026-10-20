<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\EventListener;

use BohnMedia\ContaoIdeasBundle\Author\MemberName;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\FrontendUser;
use Contao\Form;
use Contao\Widget;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Ergänzt die über "Eingaben speichern" in tl_ini_idea geschriebenen
 * Formulardaten um die Felder, die nicht aus dem Formular selbst kommen.
 *
 * Funktioniert mit und ohne Login: Gäste tragen Name und E-Mail-Adresse in
 * den Feldern authorName und authorEmail ein. Bei eingeloggten Mitgliedern
 * werden beide aus tl_member übernommen und zusätzlich memberId gesetzt.
 *
 * Angezeigt werden bevorzugt die aktuellen Daten aus tl_member, die Kopie in
 * tl_ini_idea greift erst, wenn das Mitglied gelöscht wurde.
 */
readonly class StoreIniIdeaListener
{
    public const TARGET_TABLE = 'tl_ini_idea';

    /**
     * Felder, die aus dem Formular übernommen werden dürfen. Alle übrigen
     * Formularfelder (z. B. Captcha) würden sonst zu einem SQL-Fehler führen.
     */
    private const ALLOWED_FIELDS = ['title', 'description', 'authorName', 'authorEmail'];

    /**
     * Felder, die bei eingeloggten Mitgliedern aus tl_member kommen.
     */
    private const AUTHOR_FIELDS = ['authorName', 'authorEmail'];

    public function __construct(private Security $security)
    {
    }

    /**
     * Belegt Name und E-Mail-Adresse für eingeloggte Mitglieder vor und sperrt
     * die Felder. Schreibgeschützte Felder übermittelt Contao nicht, gespeichert
     * werden die Werte in storeFormData direkt aus tl_member.
     *
     * @param array<string, mixed> $formData
     */
    #[AsHook('loadFormField')]
    public function onLoadFormField(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (self::TARGET_TABLE !== $form->targetTable || !\in_array($widget->name, self::AUTHOR_FIELDS, true)) {
            return $widget;
        }

        $user = $this->security->getUser();

        if ($user instanceof FrontendUser) {
            $widget->value = 'authorEmail' === $widget->name
                ? (string) $user->email
                : MemberName::of($user);
            $widget->readonly = true;
        }

        return $widget;
    }

    /**
     * Reichert die übermittelten Daten vor E-Mail-/Notification-Versand um die
     * Mitgliedsdaten an. Läuft vor storeFormData; die Whitelist dort hält die
     * zusätzlichen Schlüssel wieder aus dem INSERT heraus.
     *
     * Im Notification Center stehen die Werte damit als ##form_member_firstname##,
     * ##form_member_lastname## und ##form_member_email## zur Verfügung.
     *
     * @param array<string, mixed> $submittedData
     * @param array<string, mixed> $labels
     * @param array<int, mixed>    $fields
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

        // Die gesperrten Felder übermittelt Contao nicht. Gefüllt reicht eine
        // Notification mit ##form_authorName## für Mitglieder und Gäste.
        $submittedData['authorName'] = MemberName::of($user);
        $submittedData['authorEmail'] = (string) $user->email;
    }

    /**
     * @param array<string, mixed> $set
     *
     * @return array<string, mixed>
     */
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
        $set['memberId'] = 0;
        $set['invisible'] = '1';

        $user = $this->security->getUser();

        // Bei Mitgliedern kommen Name und E-Mail-Adresse immer aus tl_member,
        // ein mitgeschickter Wert darf niemanden als jemand anderen ausgeben
        if ($user instanceof FrontendUser) {
            $set['memberId'] = (int) $user->id;
            $set['authorName'] = MemberName::of($user);
            $set['authorEmail'] = (string) $user->email;
        }

        return $set;
    }

}
