<?php

declare(strict_types=1);

namespace BohnMedia\ContaoIdeasBundle\Controller\ContentElement;

use Contao\Config;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Pagination;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listet die Ideen aus tl_ini_idea im Frontend auf.
 *
 * Sichtbar sind alle freigegebenen Ideen (invisible = 0) sowie zusätzlich die
 * eigenen Ideen des eingeloggten Mitglieds, damit dieses den Status der eigenen
 * Einreichung verfolgen kann.
 */
#[AsContentElement('ini_idea_list', category: 'includes')]
class IniIdeaListController extends AbstractContentElementController
{
    private const CACHE_TAG = 'contao.db.tl_ini_idea';

    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        #[Autowire('%contao.csrf_token_name%')]
        private readonly string $csrfTokenName,
        #[Autowire(service: 'contao.cache.entity_tags')]
        private readonly EntityCacheTags $entityCacheTags,
        private readonly TranslatorInterface $translator,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        $memberId = $this->getMemberId();

        // Zurücknehmen verarbeiten, bevor gelesen wird (wirft eine Redirect-Exception)
        if ($model->iniIdeaAllowWithdraw && $memberId) {
            $this->handleWithdraw($request, $model, $memberId);
        }

        [$where, $params] = $this->buildFilter($memberId);

        $total = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM tl_ini_idea i WHERE $where",
            $params,
        );

        $offset = 0;
        $limit = null;
        $pagination = null;

        if ($model->perPage > 0 && $total > 0) {
            $param = 'page_i'.$model->id;
            $page = $request->query->getInt($param, 1);

            // Seite außerhalb des gültigen Bereichs weder indizieren noch cachen
            if ($page < 1 || $page > max(ceil($total / $model->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.$request->getUri());
            }

            $offset = ($page - 1) * $model->perPage;
            $limit = (int) $model->perPage;

            $pagination = (new Pagination(
                $total,
                (int) $model->perPage,
                (int) $this->getContaoAdapter(Config::class)->get('maxPaginationLinks'),
                $param,
            ))->generate("\n  ");
        }

        $ideas = $this->fetchIdeas($where, $params, $offset, $limit, $memberId, $model);

        // Das Backend invalidiert bei jeder Änderung den Tabellen-Tag. Nur so
        // verwirft auch eine neu freigegebene Idee, die noch in keiner
        // gecachten Liste steht, den Cache-Eintrag.
        $this->tagResponse(self::CACHE_TAG);

        $template->set('ideas', $ideas);
        $template->set('pagination', $pagination);
        $template->set('total', $total);
        $template->set('member_id', $memberId);
        $template->set('element_id', (int) $model->id);
        $template->set('request_token', $this->csrfTokenManager->getDefaultTokenValue());
        $template->set('withdrawn', $this->consumeWithdrawnFlash($request, $model));
        $template->set('show_author', (bool) $model->iniIdeaShowAuthor);

        return $template->getResponse();
    }

    /**
     * Baut die WHERE-Bedingung: freigegebene Ideen plus die eigenen.
     *
     * Der Tabellen-Alias "i" wird von beiden Abfragen (COUNT und Liste)
     * verwendet, damit die Bedingung unverändert wiederverwendbar ist.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildFilter(int|null $memberId): array
    {
        if (!$memberId) {
            return ['i.invisible = 0', []];
        }

        return ['(i.invisible = 0 OR i.memberId = :member)', ['member' => $memberId]];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchIdeas(string $where, array $params, int $offset, int|null $limit, int|null $memberId, ContentModel $model): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(
                'i.id',
                'i.createdAt',
                'i.memberId',
                'i.title',
                'i.description',
                'i.answer',
                'i.invisible',
                'i.authorName AS guestName',
                "TRIM(CONCAT(m.firstname, ' ', m.lastname)) AS authorName",
                'm.username AS authorUsername',
            )
            ->from('tl_ini_idea', 'i')
            ->leftJoin('i', 'tl_member', 'm', 'm.id = i.memberId')
            ->where($where)
            ->setParameters($params)
        ;

        // Eigene Ideen zuerst, innerhalb beider Gruppen jeweils Datum absteigend
        if ($memberId) {
            $qb->orderBy('IF(i.memberId = :member, 0, 1)', 'ASC');
        }

        $qb->addOrderBy('i.createdAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }

        $datimFormat = $this->getDatimFormat();
        $ideas = [];

        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            $isOwn = $memberId && (int) $row['memberId'] === $memberId;
            $author = $this->getAuthor($row);
            $invisible = (bool) $row['invisible'];
            $answer = trim((string) $row['answer']);
            $status = match (true) {
                $answer !== '' => 'answered',
                $invisible     => 'inProgress',
                default        => 'verified',
            };

            $ideas[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'description' => (string) $row['description'],
                'answer' => $answer,
                'author' => $author,
                'created_at' => (int) $row['createdAt'],
                'date' => Date::parse($datimFormat, (int) $row['createdAt']),
                'is_own' => $isOwn,
                'invisible' => $invisible,
                'status' => $status,
                'can_withdraw' => $isOwn && '' === $answer && (bool) $model->iniIdeaAllowWithdraw,
            ];
        }

        return $ideas;
    }

    /**
     * Nimmt eine eigene, noch unbeantwortete Idee zurück.
     */
    private function handleWithdraw(Request $request, ContentModel $model, int $memberId): void
    {
        if (!$request->isMethod('POST') || 'withdraw' !== $request->request->get('ini_idea_action')) {
            return;
        }

        // Mehrere Ideenlisten auf einer Seite auseinanderhalten
        if ((int) $request->request->get('ini_idea_element') !== (int) $model->id) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, (string) $request->request->get('REQUEST_TOKEN'));

        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidRequestTokenException('Invalid request token');
        }

        // Die WHERE-Bedingung erzwingt Eigentümerschaft und fehlende Antwort,
        // unabhängig davon, was der Client sendet.
        $deleted = $this->connection->executeStatement(
            "DELETE FROM tl_ini_idea WHERE id = :id AND memberId = :member AND (answer IS NULL OR answer = '')",
            [
                'id' => (int) $request->request->get('ini_idea_id'),
                'member' => $memberId,
            ],
        );

        // Am Backend vorbei gelöscht, daher selbst invalidieren, sonst bleibt
        // eine freigegebene Idee in gecachten Listen stehen
        if ($deleted > 0) {
            $this->entityCacheTags->invalidateTagsFor(self::CACHE_TAG);
        }

        // Der Hinweis überlebt genau eine Anfrage und taucht daher weder beim
        // Neuladen noch bei einem geteilten Link wieder auf.
        if ($request->hasSession()) {
            $session = $request->getSession();

            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add($this->getFlashType($model), true);
            }
        }

        // POST/Redirect/GET, damit ein Reload die Aktion nicht wiederholt
        $query = $request->query->all();
        $url = $request->getBaseUrl().$request->getPathInfo();

        throw new RedirectResponseException($query ? $url.'?'.http_build_query($query) : $url);
    }

    /**
     * Liest den Hinweis "Idee zurückgenommen" aus und entfernt ihn dabei.
     *
     * Eine nicht gestartete Session wird bewusst nicht angefasst: Ein Lesezugriff
     * würde sie starten und damit jede Antwort für Gäste uncachebar machen.
     */
    private function consumeWithdrawnFlash(Request $request, ContentModel $model): bool
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

    /**
     * Pro Inhaltselement ein eigener Typ, damit bei mehreren Ideenlisten auf
     * einer Seite nur die auslösende den Hinweis anzeigt.
     */
    private function getFlashType(ContentModel $model): string
    {
        return 'ini_idea_withdrawn_'.$model->id;
    }

    /**
     * Mitgliedsname, sonst der im Formular eingetragene Name (Einreichung
     * ohne Login), sonst ein Platzhalter.
     *
     * @param array<string, mixed> $row
     */
    private function getAuthor(array $row): string
    {
        $author = trim((string) $row['authorName']) ?: trim((string) $row['authorUsername']) ?: trim((string) $row['guestName']);

        if ('' !== $author) {
            return $author;
        }

        $key = (int) $row['memberId'] > 0 ? 'formerMember' : 'anonymous';

        return $this->translator->trans('MSC.iniIdea.'.$key, [], 'contao_default');
    }

    private function getMemberId(): int|null
    {
        $user = $this->security->getUser();

        return $user instanceof FrontendUser ? (int) $user->id : null;
    }

    private function getDatimFormat(): string
    {
        $pageModel = $this->getPageModel();

        return $pageModel?->datimFormat ?: (string) $this->getContaoAdapter(Config::class)->get('datimFormat');
    }
}
