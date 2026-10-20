# Contao Ideas Bundle

Idea management for Contao 5. Members submit ideas through a regular Contao
form, editors review and answer them in the back end, and a content element
lists them in the front end. The submitter gets notified via the Notification
Center as soon as their idea has been answered.

## Features

- Back end module **Content → Ideas** to review, answer, publish and hide ideas
- Content element **Idea list** with pagination and status labels
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

| Field name    | Type      |
|---------------|-----------|
| `title`       | Text      |
| `description` | Textarea  |

Only `title` and `description` are taken from the form; any other fields
(captcha, explanation, …) are ignored when storing. The bundle sets the
remaining columns itself:

- `memberId` – the logged-in member (0 for guests)
- `createdAt` / `tstamp` – the time of submission
- `invisible` – always `1`, new ideas are hidden until they are reviewed

If you send a notification from the form, the submitter's data is available
as `##form_member_firstname##`, `##form_member_lastname##` and
`##form_member_email##`.

### 2. Idea list

Add the content element **Idea list** (category *Includes*) to a page.

- **Items per page** – enables the pagination (0 shows all ideas)
- **Allow withdrawing** – members may delete their own ideas as long as they
  are unanswered

Guests only see published ideas. Logged-in members additionally see their own
unpublished ideas, listed first. Each idea carries one of these statuses:

| Status        | Condition                     |
|---------------|-------------------------------|
| `inProgress`  | hidden and not answered yet   |
| `verified`    | published, not answered yet   |
| `answered`    | an answer has been entered    |

### 3. Answer notification

Create a notification of the type **Idea answered** in the Notification Center.
It is sent once per idea when an answer is saved in the back end. The time of
sending is stored in the field *Answer notification sent on*; clear that field
to send the notification again on the next save.

Available tokens:

| Token                    | Content                       |
|--------------------------|-------------------------------|
| `##recipient_email##`    | Email address of the submitter |
| `##member_firstname##`   | First name of the submitter   |
| `##member_lastname##`    | Last name of the submitter    |
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

The markup uses BEM-style classes starting with `ini-idea` (`ini-idea--own`,
`ini-idea__answer`, …) for styling.

## Naming

The database table (`tl_ini_idea`), the content element type (`ini_idea_list`)
and the notification type (`ini_idea_answered`) keep their original `ini_`
prefix, because the bundle was extracted from an existing installation.

## License

MIT
