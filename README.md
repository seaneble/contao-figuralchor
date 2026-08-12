# Figuralchor Contao Bundle

Custom Contao bundle for figuralchor-stuttgart.de: theme design (templates,
CSS) and site-specific functionality, developed with Claude Code and deployed
onto the Contao installation via Composer.

## What lives here vs. what lives in the Contao backend

This repo owns **design and code**:
- `contao/templates/` — Twig template overrides (project-wide layout/head
  changes). Contao resolves templates by logical name through a priority
  chain: project-root `templates/` → this bundle's `contao/templates/` →
  Contao core. Placing a template here with the same logical name
  transparently overrides the core version for every layout that uses it.
- `assets/css/` — source of truth for theme CSS. Deployed by copying into the
  live install's `files/theme/` and running `contao:filesync`, so it stays
  selectable via the backend's "external style sheets" file picker on a
  Layout. (There's no native way to make bundle CSS appear as a "CSS
  Framework" checkbox like `layout.css`/`responsive.css` — that list is a
  hardcoded core DCA array with no extension point — so this file-picker
  route is intentional, not a workaround.)
- `src/` — functionality: custom content elements, insert tags, hooks, DCA
  additions, Twig extensions.

The Contao **backend** owns everything else: pages, articles and content
elements, news/calendar entries, forms, redirects, menu structure, and all
uploaded media under `files/` (except the curated `files/theme/` assets this
bundle deploys). None of that is touched by this repo.

**Template Studio caveat:** Contao's backend "Template Studio" writes
`.html.twig` files directly into the project's root `templates/` directory
(not the database). To keep `templates/`-level overrides exclusively
git-managed and avoid silent drift, Template Studio access should be
restricted to admin/dev backend user accounts (Backend Users → Groups →
module permissions).

## Theme hue feature

`ThemeHueGenerator` assigns each visitor session a random, stable hue
(0–359), stored in the session (not derived from the session ID, which isn't
generated until the session actually starts). Two ways to use it:

- **Automatic**: `contao/templates/fe_page.html.twig` and
  `contao/templates/page/layout.html.twig` inject
  `<style>:root{--theme-hue:N}</style>` into the page `<head>` via the
  `figuralchor_theme_hue()` Twig function — no backend configuration needed.
- **Manual**: the `{{theme_hue}}` insert tag is still available for editors
  who want to reference the value in ad hoc backend content.

`assets/css/theme.css` consumes the `--theme-hue` custom property
(`hsl(var(--theme-hue), ...)`) for links, buttons, and `.accent`/`.highlight`
elements — adjust selectors to match real site markup as design work
progresses.

## Design tokens: Open Props

`assets/css/vendor/open-props.min.css` is a vendored, version-pinned copy of
[Open Props](https://open-props.style) (currently 1.7.23) — a zero-build set
of CSS custom properties for spacing, type scale, color, shadows, easing,
etc. It defines tokens only (no CSS rules), so it can't conflict with
anything; `theme.css` and future component styles should build on its
`--size-*`, `--font-size-*`, and similar variables instead of hand-rolling
scales. To update, re-fetch a newer pinned version from
`https://cdn.jsdelivr.net/npm/open-props@<version>/open-props.min.css` and
replace the file — don't point at `@latest`, so deploys stay reproducible.

It's deployed into `files/theme/open-props.min.css` alongside `theme.css`
and needs to be selected in the backend's "external style sheets" picker
**before** `theme.css` in load order (Open Props only defines variables,
but keeping tokens-before-usage is the correct convention).

## Deploying

From the server (`ssh figuralchor-stuttgart.de`), run:

```
~/dev/contao-figuralchor/bin/deploy.sh
```

This pulls the latest commit, syncs `assets/css/theme.css` and
`assets/css/vendor/open-props.min.css` into the live `files/theme/`, runs
`composer update` for this package, syncs the file index, clears cache,
checks for pending migrations, and does a health check against
`test.figuralchor-stuttgart.de`.
