# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Eventos-CCM** is a Symfony 6.4 web application for managing seminars and events at the Centro de Ciencias Matemáticas (CCM) at UNAM. It handles seminar series, individual event/talk listings, organizer management, calendar export (.ics), and weekly email notifications.

## Common Commands

### Symfony Console
```bash
php bin/console cache:clear
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
php bin/console debug:router
php bin/console app:send-events           # send next-week events email
php bin/console app:send-events --current # send current-week events email
```

### Assets (Webpack Encore)
```bash
npm run dev        # one-time development build
npm run watch      # rebuild on file changes
npm run build      # production minified build
npm run dev-server # hot reload dev server
```

### Development Server
```bash
symfony server:start   # start Symfony local server
```

## Architecture

### Entities & Relationships
- **Seminario** — a recurring seminar series (name, location, weekly start time, slug)
- **EventSeminar** — an individual talk/event within a seminar (speaker, title, abstract, start/end DateTime, institution, url, notes, slug). Many-to-One with Seminario.
- **Organizer** — a person who organizes a seminar series (name, email, institution). Many-to-One with Seminario.

Slugs are auto-generated via **StofDoctrineExtensions** (Gedmo Sluggable). `EventSeminar.createdAt` is auto-set via Gedmo Timestampable.

### Controllers
| Controller | Prefix | Notable behavior |
|---|---|---|
| `EventSeminarController` | `/eventos` | Main public-facing view; token-gated event creation; prevents editing past events; JSON endpoint for FullCalendar; `.ics` export |
| `SeminarioController` | `/seminario` | CRUD for seminar series; optional token-based access |
| `OrganizerController` | `/organizer` | CRUD for organizers |

### Token-Based Access Control
Event and seminar creation/editing requires a `SEMINARIO_TOKEN` env variable match — this is NOT Symfony's security firewall. The token is passed in the URL and compared in the controller.

### Forms
`EventSeminarType` uses split date/time fields (`start_date`, `start_time`, `event_duration`) that are not mapped to the entity. The controller merges them into `DateTime` objects manually before persisting.

### Repositories
`EventSeminarRepository::findEventsBetweenDates(DateTimeInterface $start, $end)` is the key query method used both by the index page (current/next week view) and by the `app:send-events` command.

### Frontend
- **Bootstrap 5.3** for layout and forms
- **FullCalendar 6.1** for the week-view calendar widget on the events index page; fetches from `/eventos/consulta/json`
- **MathJax 3** (CDN) for LaTeX rendering in abstracts
- **Bootstrap Icons** for UI icons
- Assets bundled with Webpack Encore; entry point is `assets/app.js`

### Email
- `app:send-events` command renders `templates/emails/send_events.{html,txt}.twig` and sends via Symfony Mailer
- `MAILER_DSN=null://null` by default — configure in `.env.local` for actual sending
- Sender: `swmail@matmor.unam.mx`, recipient: `miguel@matmor.unam.mx`

### Pagination
KnpPaginator is configured in `config/packages/knp_paginator.yaml` with Bootstrap v5 templates. The events index uses 8 items per page.

## Key Environment Variables
- `DATABASE_URL` — set in `.env.local` (not committed)
- `SEMINARIO_TOKEN` — token for gating create/edit access
- `MAILER_DSN` — email transport (null by default)
- `MESSENGER_TRANSPORT_DSN` — Doctrine-backed async queue for notifications
