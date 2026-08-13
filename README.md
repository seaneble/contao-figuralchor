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

`assets/css/theme.css` derives the entire color system from
`--theme-hue` via `oklch(L C H)` with fixed lightness/chroma per role
(background, text, accent, focus ring, etc.) — OKLCH's lightness is
perceptually uniform across hues, so contrast between roles stays consistent
no matter which hue a visitor lands on. Verified numerically across all 360
hues (not just eyeballed): worst case is 5.15:1 for text/UI pairs, well
clear of WCAG AA's 4.5:1. Don't reintroduce `hsl()` for anything
contrast-sensitive — it doesn't have this property (equal lightness values
don't look equally light across hues), which is exactly the problem OKLCH
was chosen to avoid.

## Layout

Built directly on the actual rendered markup of the two layouts currently
in use ("Standard", "Startseite" — both render through `fe_page.html.twig`,
confirmed by checking `tl_layout.template` directly rather than assuming).

- **Header**: `contao/templates/fe_page.html.twig` (and
  `page/layout.html.twig` for forward-compatibility, unused today) injects
  an `<h1 class="site-branding">` logo before the header module content.
  It hardcodes the path
  `/files/theme/logo/Logo_Figuralchor_schwarz_mit_Schrift.svg` — if that
  file is ever renamed/moved/deleted in the file manager, update the path
  in both templates to match.
- **Navigation**: `contao/templates/mod_navigation.html.twig` overrides
  Contao's default nav template to wrap the menu in a `<details>/<summary>`
  disclosure — a zero-JavaScript, natively accessible mobile toggle (shows
  as a corner button below the nav breakpoint, `64rem`/1024px). Above that
  width, CSS hides the toggle and shows the menu as a normal horizontal
  bar; submenus become hover/focus dropdowns. This applies automatically to
  every `navigation`-type module (no backend template selection needed —
  it's a transparent override of the default logical template name).
  **Gotcha**: Contao's `mod_customnav.html.twig` (used by the footer's
  "Navigation: Fußzeile" module) is itself just `{% extends
  '@Contao/mod_navigation.html.twig' %}` with no overrides — so it inherits
  our toggle too. `theme.css`'s `#footer` rules explicitly neutralize it
  (hide the toggle button, force the list static/flex) so the footer stays
  a plain wrapping row of links. Keep that in mind if a *third* nav-type
  module gets added somewhere new.
- **Member-only menu items**: no code involved — Contao's page-level
  "Access protection" (`protected` + `groups` fields) already controls
  this natively; protected pages are hidden from the nav for guests and
  shown automatically to members of the right group. Several pages under
  "Interna" already use this.
- **Sidebars**: today's layouts don't define left/right columns, but
  `#container` in `theme.css` is already built to handle them once a
  Layout's module definitions add one: below `64rem` everything stacks in
  document order (main, then left, then right — i.e. sidebars fold below
  the main content by default). Above `64rem`, `:has()` selectors detect
  which of `#left`/`#right` actually exist and lay out the grid columns
  accordingly. To *hide* a sidebar module completely on narrow screens
  instead of folding it below, add the CSS class `u-hide-narrow` to it via
  the module's "CSS ID/class" field in the backend.
- **Homepage tiles**: no new template — `#main .inside` becomes a
  responsive grid above `48rem` when it contains `.mod_eventlist`/
  `.mod_listing` modules (both already present on the "Startseite" layout:
  the two "Termin-Teaser" event lists and the "Konzertliste" listing), each
  styled as a card. The intro article always spans the full width.
- **Forms**: generic element selectors (`input`, `textarea`, `select`,
  `button`, `label`, `fieldset`) are styled consistently — not tied to a
  specific Contao form's field classes, so it applies to any form module
  (login, search, member forms, Contao forms) without per-form setup.

## Calendar: mobile list

The internal calendar (`/interna/termine`, module "Kalender", pulling from
calendars 1+2) is a 7-column month grid - fine on desktop, cramped on any
phone. Rather than force the grid to survive narrow screens, add a second
module on the same page:

1. Create a new module of type **Veranstaltungsliste (eventlist)**, select
   the same two calendars (**Probentermine** and **Auftritte**) the
   existing "Kalender" module uses.
2. Give it the CSS class **`calendar-mobile-list`** in its "CSS-ID/Klasse"
   field (Expert settings).
3. Place it on the Termine page in the same position as the existing
   calendar module.

`theme.css` then handles the rest automatically: below 768px the grid
hides and this list shows; at 768px and up, the reverse. Until this
module exists, the grid just stays visible everywhere with a horizontal-
scroll fallback (`overflow-x: auto`) so it's still usable, never both-or-
neither.

## Fonts

`--font-body` tries `"Helvetica Neue"` and `Helvetica` first, falling back
to the OS system font. **No font files are embedded.** Listing a font name
in a CSS stack is free and legal (it only applies if the visitor's device
already has it — true for most Mac/iOS users); actually *embedding*
Helvetica Neue as a webfont requires a separate web-font license that a
desktop/print license doesn't automatically grant. Don't add embedded
Helvetica Neue font files here without confirming that license first.

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
