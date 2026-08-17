# Working on this repo (for Claude Code / other agents)

This file is about *process* — how to work in this repo day to day. For
what the code does and why, see `README.md`.

## Where things live

- **This local clone** (`/Users/sebastian/Public/Figuralchor`) — edit here
  directly with normal file tools. It's a plain clone of
  `git@github.com:seaneble/contao-figuralchor.git` (public repo, `master`
  branch, no PRs — commit straight to master). Push from here normally;
  don't route commits through the server-side checkout described below
  unless this local clone is unavailable.
- **The live server** (`ssh figuralchor-stuttgart.de`) — only needed for:
  1. Deploying (`bin/deploy.sh`, see below).
  2. Read-only inspection of the live Contao install (DB queries via
     `contao-console dbal:run-sql`, checking rendered HTML, tailing logs).
  3. A second checkout at `~/dev/contao-figuralchor` exists there too (used
     before this local clone existed) — keep it in sync via `git pull` if
     you ever edit through it, but prefer editing locally now.
- **The public domain is not live yet.** `figuralchor-stuttgart.de` itself
  302-redirects to an unrelated parish page (intentional, IONOS-level
  forwarding, stays until launch). Always test against
  **`https://test.figuralchor-stuttgart.de`** instead.

Full server layout, directory structure, and IONOS-specific gotchas
(broken composer auth token, `PATH_INFO` stripping, PHP CLI binary names)
are in this session's persistent memory, not repeated here since that's
infrastructure knowledge rather than repo content — ask if you need it
re-derived, or it'll already be loaded for sessions that have it.

## Deploying a change

1. Commit and push from the local clone as normal.
2. `ssh figuralchor-stuttgart.de "bash ~/dev/contao-figuralchor/bin/deploy.sh"`
   — pulls latest `master`, copies `assets/css/*.css` into the live
   install's `files/theme/`, runs `composer update` for this package
   (isolated via a throwaway `COMPOSER_HOME` — the account's real
   `~/.composer/auth.json` has a stale github-oauth token that breaks any
   Composer command touching github.com otherwise), syncs the Contao file
   index, clears cache, checks migrations, and health-checks
   `test.figuralchor-stuttgart.de`.
3. **CSS changes need step 2's `files/theme/` copy to actually go live** —
   the site serves CSS through Contao's backend-selected "external style
   sheet" file picker (see README), not directly from `vendor/`. Editing
   `assets/css/theme.css` alone and stopping at `composer update` silently
   ships nothing.
4. Verify post-deploy with a plain `curl` against `test.figuralchor-
   stuttgart.de` for anything public, or against a real page/asset path —
   don't just trust "deploy complete" output. Past sessions have caught
   real bugs this way (a stale `files/theme/theme.css` copy that
   `composer update` alone never touched).
5. Schema/DB changes: run `contao:migrate --dry-run` first and read the
   diff before running it for real — it's the live production DB.

## Testing behind a login

No browser tool is available. To check a protected page's actual
rendered HTML, log in via `curl` with a cookie jar against Contao's
front-end login form (`FORM_SUBMIT`, `REQUEST_TOKEN`, `_target_path`,
`username`, `password` fields — scrape current values from the login
page first, the token is single-use/session-bound):

```
curl -sL -c cookies.txt -o page.html https://test.figuralchor-stuttgart.de/<login-page-alias>
# scrape FORM_SUBMIT / REQUEST_TOKEN / _target_path out of page.html
curl -sL -c cookies.txt -b cookies.txt --data-urlencode 'FORM_SUBMIT=...' ... https://test.figuralchor-stuttgart.de/<login-page-alias>
curl -sL -c cookies.txt -b cookies.txt https://test.figuralchor-stuttgart.de/<protected-page>
```

Credentials, when shared in a session, are for this kind of read-only
verification only — use them server-side in `curl` calls, don't paste them
into files, commits, or memory.

## Guardrails that have actually fired in this repo

- **Never read `.env`/`.env.local` or any secrets file**, even redacted —
  blocked by the sandbox and rightly so; ask the user for the specific
  value instead if it's truly needed.
- **Never write test/throwaway data into live production tables** (e.g.
  `UPDATE tl_calendar_events ... WHERE id=(SELECT ... LIMIT 1)` to
  smoke-test an insert tag) — also blocked, also correct. Verify new
  backend code via `lint:twig`, `php -l`, `contao-console cache:warmup`
  (fails loudly on bad DI wiring) and static review instead; if genuine
  runtime verification needs real data, ask the user to add it through
  the backend themselves.
- **Schema-migrating a live column is a real decision, not a default** —
  when a DCA field changes type/meaning (e.g. year → full date), ask
  whether existing rows should be auto-migrated with an assumption or
  left blank for manual re-entry. Don't silently pick one.

## Git conventions here

- Commit granularity: one logical change per commit, each with a
  multi-paragraph message explaining *why*, matching the existing log
  (`git log --oneline` reads like a changelog — keep it that way).
  Several small fixes requested in one conversation still get split into
  separate commits by topic, not squashed into one "misc fixes" commit.
- Always create new commits; don't amend published history here.
- No `--no-verify`, no force-push — this is a two-person hobby project
  but treated with the same care as any shared repo.
