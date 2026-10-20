<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Controller\ContentElement;

use BohnMedia\ContaoIdeasBundle\Author\MemberName;
use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\FormCaptcha;
use Contao\FormCheckbox;
use Contao\FormSubmit;
use Contao\FormText;
use Contao\FormTextarea;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\NotificationCenterBundle\NotificationCenter;

/**
 * Formular zum Einreichen einer Idee, mit und ohne Login.
 *
 * Aufgebaut aus den Contao-Formular-Widgets und gerendert über deren
 * Templates (form_inline, form_text, …), sodass es aussieht und sich verhält
 * wie ein Formular aus dem Formulargenerator.
 *
 * Bei Mitgliedern kommen Name und E-Mail-Adresse aus tl_member, die Felder
 * werden vorbelegt und gesperrt. Gäste tragen beides selbst ein und müssen
 * zusätzlich die Sicherheitsfrage beantworten.
 */
#[AsContentElement('ini_idea_form', category: 'ini_ideas')]
class IniIdeaFormController extends AbstractContentElementController
{
    use BackendWildcardTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly NotificationCenter $notificationCenter,
        #[Autowire(service: 'contao.insert_tag.parser')]
        private readonly InsertTagParser $insertTagParser,
        #[Autowire(service: 'contao.routing.content_url_generator')]
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if ($this->isBackendScope($request)) {
            return $this->getBackendWildcard($model);
        }

        $this->initializeContaoFramework();

        $user = $this->security->getUser();
        $member = $user instanceof FrontendUser ? $user : null;

        if ($this->consumeConfirmation($request, $model)) {
            $template->set('message', $model->iniIdeaConfirmation ?: '<p>'.$this->trans('confirmation').'</p>');
            $template->set('form', null);

            return $template->getResponse();
        }

        $formId = 'ini_idea_form_'.$model->id;
        $widgets = $this->createWidgets($model, $member);

        if ($request->isMethod('POST') && $formId === $request->request->get('FORM_SUBMIT')) {
            $hasErrors = false;

            foreach ($widgets as $widget) {
                $widget->validate();
                $hasErrors = $widget->hasErrors() || $hasErrors;
            }

            // Wirft bei Erfolg eine Redirect-Exception (POST/Redirect/GET)
            if (!$hasErrors) {
                $this->submit($widgets, $model, $member, $request);
            }
        }

        $form = new FrontendTemplate('form_inline');
        $form->setData([
            'id' => $model->id,
            'formSubmit' => $formId,
            'method' => 'post',
            'action' => '',
            'enctype' => 'application/x-www-form-urlencoded',
            'attributes' => ' class="ini-idea-form"',
            'novalidate' => '',
            'errors' => [],
            'hidden' => '',
            'maxFileSize' => false,
            'ajax' => false,
            'fields' => implode('', array_map(static fn (Widget $widget): string => $widget->parse(), $widgets)),
        ]);

        $template->set('message', null);
        $template->set('form', $form->parse());

        return $template->getResponse();
    }

    /**
     * @return array<string, Widget>
     */
    private function createWidgets(ContentModel $model, FrontendUser|null $member): array
    {
        $widgets = [];

        // Absender oben, darunter Titel und Text, wie bei einer E-Mail
        $widgets['authorName'] = $this->createWidget(FormText::class, $model, 'authorName', null === $member, ['maxlength' => 255]);
        $widgets['authorEmail'] = $this->createWidget(FormText::class, $model, 'authorEmail', null === $member && $model->iniIdeaEmailMandatory, ['maxlength' => 255, 'rgxp' => 'email']);
        $widgets['title'] = $this->createWidget(FormText::class, $model, 'title', true, ['maxlength' => 255]);
        $widgets['description'] = $this->createWidget(FormTextarea::class, $model, 'description', (bool) $model->iniIdeaDescriptionMandatory, ['rows' => 8, 'cols' => 40]);

        // Mitglieder sehen ihre Daten, ändern können sie sie hier nicht.
        // Gespeichert wird ohnehin der Stand aus tl_member.
        if (null !== $member) {
            $widgets['authorName']->value = MemberName::of($member);
            $widgets['authorName']->readonly = true;
            $widgets['authorEmail']->value = (string) $member->email;
            $widgets['authorEmail']->readonly = true;
        }

        if ($model->iniIdeaPrivacy) {
            $text = $model->iniIdeaPrivacyText ?: $this->trans('privacy');

            $widgets['privacy'] = $this->createWidget(FormCheckbox::class, $model, 'privacy', true, [
                'label' => '',
                'options' => [['value' => '1', 'label' => $this->insertTagParser->replaceInline($text)]],
            ]);
        }

        if (null === $member) {
            $widgets['captcha'] = $this->createWidget(FormCaptcha::class, $model, 'captcha', true);
        }

        $widgets['submit'] = $this->createWidget(FormSubmit::class, $model, '', false, [
            'label' => '',
            'slabel' => $this->trans('submit'),
        ]);

        return $widgets;
    }

    /**
     * @param class-string<Widget> $class
     * @param array<string, mixed> $attributes
     */
    private function createWidget(string $class, ContentModel $model, string $name, bool $mandatory, array $attributes = []): Widget
    {
        $widget = new $class([
            'id' => 'ini_idea_'.$model->id.'_'.($name ?: 'submit'),
            'name' => $name,
            'label' => '' !== $name ? $this->trans($name) : '',
            'mandatory' => $mandatory,
            'decodeEntities' => true,
            ...$attributes,
        ]);

        $widget->required = $mandatory;

        return $widget;
    }

    /**
     * @param array<string, Widget> $widgets
     *
     * @throws Exception
     */
    private function submit(array $widgets, ContentModel $model, FrontendUser|null $member, Request $request): never
    {
        $time = time();

        $idea = [
            'tstamp' => $time,
            'createdAt' => $time,
            'memberId' => null !== $member ? (int) $member->id : 0,
            'title' => (string) $widgets['title']->value,
            'description' => (string) $widgets['description']->value,
            'authorName' => null !== $member ? MemberName::of($member) : (string) $widgets['authorName']->value,
            'authorEmail' => null !== $member ? (string) $member->email : (string) $widgets['authorEmail']->value,
            'invisible' => 1,
        ];

        $this->connection->insert('tl_ini_idea', $idea);

        $id = (int) $this->connection->lastInsertId();

        if ($model->iniIdeaNotification) {
            $this->notificationCenter->sendNotification((int) $model->iniIdeaNotification, [
                'author_email' => $idea['authorEmail'],
                'author_name' => $idea['authorName'],
                'is_member' => null !== $member ? '1' : '0',
                'idea_title' => $idea['title'],
                'idea_description' => $idea['description'],
                'idea_created_at' => Date::parse(Config::get('datimFormat'), $time),
                'idea_id' => (string) $id,
                'idea_backend_url' => $this->router->generate(
                    'contao_backend',
                    ['do' => 'ini_ideas', 'act' => 'edit', 'id' => $id],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ]);
        }

        if ($model->iniIdeaJumpTo && ($page = PageModel::findById($model->iniIdeaJumpTo))) {
            throw new RedirectResponseException($this->contentUrlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        // Bestätigung für genau eine Anfrage, damit sie beim Neuladen nicht
        // wieder erscheint
        if ($request->hasSession()) {
            $session = $request->getSession();

            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add($this->getFlashType($model), true);
            }
        }

        $query = $request->query->all();
        $url = $request->getBaseUrl().$request->getPathInfo();

        throw new RedirectResponseException($query ? $url.'?'.http_build_query($query) : $url);
    }

    /**
     * Liest die Bestätigung aus und entfernt sie dabei. Eine nicht gestartete
     * Session wird nicht angefasst, sonst wäre die Seite für Gäste uncachebar.
     */
    private function consumeConfirmation(Request $request, ContentModel $model): bool
    {
        if (!$request->hasSession(true)) {
            return false;
        }

        $session = $request->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface || !$session->isStarted()) {
            return false;
        }

        return (bool) $session->getFlashBag()->get($this->getFlashType($model));
    }

    private function getFlashType(ContentModel $model): string
    {
        return 'ini_idea_submitted_'.$model->id;
    }

    private function trans(string $key): string
    {
        return $this->translator->trans('MSC.iniIdea.form.'.$key, [], 'contao_default');
    }
}
