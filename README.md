# Contao Ideas Bundle

Idea management for Contao 5: members or guests submit ideas, editors answer
them in the back end, and a list shows them in the front end.

## Requirements

- PHP 8.2+
- Contao 5.3+
- [terminal42/notification_center](https://github.com/terminal42/contao-notification_center) 2.x

## Installation

```bash
composer require bohnmedia/contao-ideas-bundle
vendor/bin/contao-console contao:migrate
```

## Usage

The back end module **Content → Ideas** manages the ideas. New ideas are
hidden until an answer is saved for the first time.

Both content elements are found in the category **Idea management**.

### Submit idea

Form with name, email address, title and description. For logged-in members,
name and email address are taken from `tl_member` and shown read-only; guests
fill them in and answer a security question. The form uses Contao's default
form templates.

Settings: mandatory description, mandatory email address, privacy checkbox,
confirmation text or redirect page, notification of the type **Idea submitted**.

### Idea list

Shows published ideas; logged-in members additionally see their own ideas at
the top. Settings: items per page, show author, allow withdrawing unanswered
ideas. Template: `content_element/ini_idea_list.html.twig`.

## Notifications

**Idea submitted** – sent by the *Submit idea* element:

| Token | Content |
|---|---|
| `##author_name##`, `##author_email##` | Submitter |
| `##is_member##` | `1` for members, `0` for guests |
| `##idea_title##`, `##idea_description##` | Idea |
| `##idea_created_at##`, `##idea_id##` | Date and ID |
| `##idea_backend_url##` | Link to the idea in the back end |

**Idea answered** – sent once per idea when an answer is saved. Clear the field
*Answer notification sent on* to send it again.

| Token | Content |
|---|---|
| `##recipient_email##`, `##author_name##` | Submitter |
| `##member_firstname##`, `##member_lastname##` | Members only |
| `##idea_title##`, `##idea_description##`, `##idea_answer##` | Idea and answer (HTML) |
| `##idea_created_at##`, `##idea_id##` | Date and ID |

## Form generator

Instead of the *Submit idea* element, a form with **Store data** into
`tl_ini_idea` can be used. Fields: `title`, `description` and, for guests,
`authorName` and `authorEmail`.

## License

MIT
