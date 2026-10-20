# Contao Ideas Bundle

Idea management for Contao 5. Ideas are submitted through a content element –
either by logged-in members or by guests who enter their name and email
address – reviewed and answered in the back end, and listed in the front end by
a second content element. The submitter gets notified via the Notification Center as
soon as their idea has been answered.

## Features

- Back end module **Content → Ideas** to review, answer, publish and hide ideas
- Content element **Submit idea**, built from Contao's form widgets and
  rendered with the regular form templates
- Content element **Idea list** with pagination and status labels
- Works with and without front end login: members are linked to their
  `tl_member` record, guests enter name and email address in the form
- Members see their own ideas (including unpublished ones) at the top of the
  list and can optionally withdraw them as long as they are unanswered
- Ideas are published automatically when they get answered for the first time
- Notification types **Idea submitted** and **Idea answered** for
  [terminal42/notification_center][nc]
- Alternatively, ideas can still be submitted through a form of the form
  generator

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

### 1. Submit idea

Add the content element **Submit idea** (category *Idea management*) to a page.

**Form**

- **Description is mandatory**
- **Email address is mandatory** – for guests; only with an address they
  receive the answer notification
- **Show privacy checkbox** – with an optional text; HTML and insert tags are
  allowed, e.g. `Ich habe die <a href="{{link_url::12}}">Datenschutzerklärung</a> gelesen.`

**After submitting**

- **Confirmation text** – shown instead of the form; leave empty for the
  default text
- **Redirect page** – redirect to this page instead of showing the text
- **Notification** – a notification of the type **Idea submitted**, e.g. to
  inform the editors or to confirm the receipt to the submitter

The element works with and without login. To restrict it to members, protect
the page or the element as usual in Contao.

The form contains name and email address at the top, followed by title and
description – like sender, subject and text of an email –, the privacy
checkbox if enabled and, for guests, Contao's security question. For members,
name and email address are prefilled from `tl_member` and read-only, and the
security question is left out. New ideas are always stored hidden until they
are reviewed.

The labels come from the language files (`MSC.iniIdea.form.*`) and can be
changed in `contao/languages` of your project.

Tokens of the notification type **Idea submitted** (`##admin_email##` is added
by the Notification Center):

| Token                    | Content                                   |
|--------------------------|-------------------------------------------|
| `##author_name##`        | Name of the submitter (member or guest)   |
| `##author_email##`       | Email address of the submitter            |
| `##is_member##`          | `1` if a member submitted the idea, else `0` |
| `##idea_title##`         | Title of the idea                         |
| `##idea_description##`   | Description of the idea                   |
| `##idea_created_at##`    | Submission date                           |
| `##idea_id##`            | ID of the idea                            |
| `##idea_backend_url##`   | Link to the idea in the back end          |

### 2. Idea list

Add the content element **Idea list** (category *Idea management*) to a page.

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

## Submitter data

Ideas of members are linked to `tl_member` via `memberId`. Name and email
address are additionally copied to the idea (`authorName`, `authorEmail`).
Guests enter both themselves.

The front end, the back end and the notifications always prefer the current
data from `tl_member`, so a changed name shows up right away. The copy is only
used once the member has been deleted. It is written on submission and kept up
to date by an hourly cron job that compares all ideas with `tl_member`. A name
changed less than an hour before the member is deleted may therefore be lost;
the idea then keeps the previous name. Ideas submitted before version 1.1 are
filled in from `tl_member` by a migration.

Contao runs cron jobs on page requests by default. On sites with little
traffic, set up a system cron job so the hourly sync runs reliably:

```
* * * * * /path/to/php /path/to/project/vendor/bin/contao-console contao:cron
```

## Alternative: form generator

Instead of the **Submit idea** element, a form of the form generator can be
used. Enable **Store data** with the target table `tl_ini_idea` and use these
field names:

| Field name    | Type            | Needed for                     |
|---------------|-----------------|--------------------------------|
| `title`       | Text            | always                         |
| `description` | Textarea        | always                         |
| `authorName`  | Text            | submissions without login      |
| `authorEmail` | Text (email)    | answer notification for guests |

Only these four fields are stored; the bundle sets `memberId`, `createdAt`,
`tstamp` and `invisible` itself. For members, `authorName` and `authorEmail`
are prefilled read-only and always taken from `tl_member`. In the form's
notification, `##form_authorName##` and `##form_authorEmail##` contain the
submitter's data in both cases; for members, `##form_member_firstname##`,
`##form_member_lastname##` and `##form_member_email##` are available as well.

## Publishing rules

When an answer is entered for the first time, the idea is published
automatically. Removing the answer or hiding an already answered idea again is
left to the editor – saving it afterwards does not publish it a second time.

## Templates

The **Submit idea** element uses `content_element/ini_idea_form.html.twig` for
its frame and Contao's default form templates (`form_inline`, `form_text`,
`form_textarea`, `form_checkbox`, `form_captcha`, `form_submit`) for the form
itself, so it looks like any other form of the page. Changes to these
templates in your project apply to this form as well.

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

The database table (`tl_ini_idea`), the content element types
(`ini_idea_list`, `ini_idea_form`) and the notification types
(`ini_idea_answered`, `ini_idea_submitted`) use the `ini_` prefix of the
installation the bundle was extracted from.

## License

MIT
