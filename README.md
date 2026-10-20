# Contao Ideas Bundle

Idea management for Contao 5. Ideas are submitted through a regular Contao
form – either by logged-in members or by guests who enter their name and email
address – reviewed and answered in the back end, and listed in the front end by
a content element. The submitter gets notified via the Notification Center as
soon as their idea has been answered.

## Features

- Back end module **Content → Ideas** to review, answer, publish and hide ideas
- Content element **Idea list** with pagination and status labels
- Works with and without front end login: members are linked to their
  `tl_member` record, guests enter name and email address in the form
- Members see their own ideas (including unpublished ones) at the top of the
  list and can optionally withdraw them as long as they are unanswered
- Ideas are published automatically when they get answered for the first time
- Notification type **Idea answered** for [terminal42/notification_center][nc]
- Form hooks that take care of the submitter, timestamps and visibility, so a
  plain Contao form is all you need for submitting ideas

## Requirements

- PHP 8.2+
- Contao 5.3+
- terminal42/notification_center 2.x

## Installation

```bash
composer require bohnmedia/contao-ideas-bundle
vendor/bin/contao-console contao:migrate
```

## Setup

### 1. Submission form

Create a form with the following fields and enable **Store data** with the
target table `tl_ini_idea`:

| Field name    | Type            | Needed for                    |
|---------------|-----------------|-------------------------------|
| `title`       | Text            | always                        |
| `description` | Textarea        | always                        |
| `authorName`  | Text            | submissions without login     |
| `authorEmail` | Text (email)    | answer notification for guests |

Only these four fields are taken from the form; any other fields (captcha,
explanation, …) are ignored when storing. The bundle sets the remaining
columns itself:

- `memberId` – the logged-in member (0 for guests)
- `createdAt` / `tstamp` – the time of submission
- `invisible` – always `1`, new ideas are hidden until they are reviewed

**With login:** `authorName` and `authorEmail` are optional. If they are part of
the form, they are prefilled with the member's data and made read-only. Either
way, name and email address are copied from `tl_member` when storing – posted
values are ignored, so nobody can submit an idea in someone else's name.

The front end, the back end and the answer notification always prefer the
current data from `tl_member`, so a changed name shows up right away. The copy
is only used once the member has been deleted. It is written on submission and
kept up to date by an hourly cron job that compares all ideas with `tl_member`.
A name changed less than an hour before the member is deleted may therefore be
lost; the idea then keeps the previous name.

Ideas submitted before version 1.1 are filled in from `tl_member` by a
migration.

Contao runs cron jobs on page requests by default. On sites with little
traffic, set up a system cron job so the hourly sync runs reliably:

```
* * * * * /path/to/php /path/to/project/vendor/bin/contao-console contao:cron
```

**Without login:** `authorName` and `authorEmail` are stored with the idea.
Add a captcha to keep spam out; it is ignored when storing. The email address
is only used for the answer notification and never shown in the front end.

If you send a notification from the form, `##form_authorName##` and
`##form_authorEmail##` contain the submitter's data in both cases. For members,
`##form_member_firstname##`, `##form_member_lastname##` and
`##form_member_email##` are available as well.

### 2. Idea list

Add the content element **Idea list** (category *Includes*) to a page.

- **Items per page** – enables the pagination (0 shows all ideas)
- **Show author** – shows the submitter's name; disable it to show the date
  only, e.g. on public pages
- **Allow withdrawing** – members may delete their own ideas as long as they
  are unanswered

Guests only see published ideas. Logged-in members additionally see their own
unpublished ideas, listed first. The author is the member's name, otherwise
the stored name. Only ideas whose member was deleted before version 1.1 are
shown as *Former member*. Each idea carries one of these statuses:

| Status        | Condition                     |
|---------------|-------------------------------|
| `inProgress`  | hidden and not answered yet   |
| `verified`    | published, not answered yet   |
| `answered`    | an answer has been entered    |

### 3. Answer notification

Create a notification of the type **Idea answered** in the Notification Center.
It is sent once per idea when an answer is saved in the back end – to the
member's email address, or to `authorEmail` for submissions without login.
Ideas without any email address are skipped. The time of
sending is stored in the field *Answer notification sent on*; clear that field
to send the notification again on the next save.

Available tokens:

| Token                    | Content                       |
|--------------------------|-------------------------------|
| `##recipient_email##`    | Email address of the submitter |
| `##author_name##`        | Name of the submitter (member or guest) |
| `##member_firstname##`   | First name of the submitter (members only) |
| `##member_lastname##`    | Last name of the submitter (members only) |
| `##idea_title##`         | Title of the idea             |
| `##idea_description##`   | Description of the idea       |
| `##idea_answer##`        | Answer (HTML)                 |
| `##idea_created_at##`    | Submission date               |
| `##idea_id##`            | ID of the idea                |

## Publishing rules

When an answer is entered for the first time, the idea is published
automatically. Removing the answer or hiding an already answered idea again is
left to the editor – saving it afterwards does not publish it a second time.

## Templates

The list is rendered by `content_element/ini_idea_list.html.twig`. Override it
in your project as usual, e.g. in `templates/content_element/ini_idea_list.html.twig`,
and extend the original to change single blocks:

```twig
{% extends "@Contao/content_element/ini_idea_list.html.twig" %}

{% block idea %}
    {{ parent() }}
{% endblock %}
```

The markup uses BEM-style classes starting with `ini-idea` for styling. Each
list item carries its status as modifier (`ini-idea--inProgress`,
`ini-idea--verified`, `ini-idea--answered`), own ideas additionally
`ini-idea--own`.

## Caching

The list is tagged with `contao.db.tl_ini_idea`. Contao invalidates this tag
whenever an idea is created, changed or deleted in the back end, and the bundle
invalidates it when a member withdraws an idea, so cached pages always show
the current list.

## Naming

The database table (`tl_ini_idea`), the content element type (`ini_idea_list`)
and the notification type (`ini_idea_answered`) keep their original `ini_`
prefix, because the bundle was extracted from an existing installation.

## License

MIT
