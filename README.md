# OpenTicket

An MIT-licensed event ticketing starter. Discover events, reserve free tickets, create events, export attendees, and check guests in.

**Status: v0.1 alpha.** Not yet a production paid-ticket platform.

## Working features

- Responsive event discovery, filters, search, and shareable event links
- Free ticket reservations (1–6 tickets per booking), persistent D1 storage
- Capacity enforced in one conditional SQL insert, avoiding overselling
- My tickets with printable booking codes and pre-admission cancellation
- Organizer event creation, attendee totals, safe CSV export
- Organizer-only admission with atomic duplicate check-in prevention
- Server-side validation, same-origin write checks, owner-scoped records

Sample events are fictional fixtures to exercise the flow. They are not real gatherings.
Check-in uses booking codes; QR scanning and ticket emails are not implemented.

## Stack

TypeScript, React, Vinext (Next-compatible APIs), Cloudflare Workers, D1 SQLite, Drizzle migrations. This first implementation targets Cloudflare, rather than PostgreSQL. Database access is isolated under `lib/server.ts` and `db/` for future adapters.

## Local setup

Requires Node >=22.13, Git, and pnpm. The project contains a lockfile.

```sh
pnpm install --frozen-lockfile
pnpm run build
node --import ./scripts/sites-env.mjs ./node_modules/wrangler/bin/wrangler.js d1 execute DB --local --config dist/server/wrangler.json --persist-to .wrangler/state --file drizzle/0000_curved_speedball.sql
pnpm run dev
```

The standalone portable profile defaults automatically. Visit the URL printed by the dev server. Loopback-only development sign-in is available at `/signin-with-chatgpt?return_to=/` in portable mode. This is a local test identity, not production authentication.

The private hosted preview uses dispatch-owned ChatGPT sign-in. For independent Cloudflare hosting, implement a production authentication adapter before exposing writes. **Never trust incoming `oai-authenticated-user-*` headers on an unprotected independent deployment.** Current authentication is valid only behind the Sites dispatch that strips/injects identity headers. The auth adapter is `app/chatgpt-auth.ts`. Standard organizer/customer signup and roles are roadmap items.

Date/time inputs use the creator's browser timezone. Explicit per-event timezone support is a roadmap item.

## Verify

```sh
pnpm exec tsc --noEmit
python3 tests/test_booking.py
pnpm run build
```

The SQLite tests cover the reservation SQL, quantity release on cancellation, owner restrictions, concurrent capacity enforcement, and duplicate admission. Browser QA has not been run for this release.

## GitHub release

Create an empty **public** repository named `open-ticket` in your GitHub account, unzip this source, then:

```sh
git init
git add .
git commit -m "Start OpenTicket v0.1"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/open-ticket.git
git push -u origin main
```

The downloadable source excludes hosted project identity, Git history, runtime data, and dependencies. `.openai/hosting.json` in the package declares `DB` without a hosted project ID. Do not copy another deployment's database or credentials.

See [CONTRIBUTING.md](CONTRIBUTING.md), [docs/ROADMAP.md](docs/ROADMAP.md), and [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).
