# Napkin Runbook

## Curation Rules
- Re-prioritize on every read.
- Keep recurring, high-value notes only.
- Max 10 items per category.
- Each item includes date + "Do instead".

## Execution & Validation (Highest Priority)
1. **[2026-06-06] Public Livewire registration pages need settings rows in tests**
   Do instead: use `RefreshDatabase`, disable Vite, and seed/update `general` settings before asserting route rendering.
2. **[2026-06-07] Filament package translations use Laravel vendor overrides**
   Do instead: add package keys under `lang/vendor/{package}/pt_BR/*.php` and validate with `php artisan tinker --execute='app()->setLocale("pt_BR"); echo __("package::file.key");'`.
3. **[2026-06-07] Filament admin table theming is global CSS**
   Do instead: style `.fi-ta-*` selectors in `resources/css/filament/admin/theme.css`, then validate with `npm run build`, focused PHP tests, and `tests/Browser/admin-panel-layout.spec.js`.
4. **[2026-06-06] Browser checks may run through Vite dev assets**
   Do instead: check `public/hot` and the loaded script URLs before trusting production-build screenshots.
5. **[2026-06-07] Authenticated Filament layout needs real-login browser checks**
   Do instead: use Playwright to log in, assert admin cookie consent is hidden, verify document/table overflow, and capture dashboard/list screenshots.
6. **[2026-06-07] Filament dropdown tests need client readiness**
   Do instead: wait for `window.Alpine && window.Livewire`, then interact with `.fi-dropdown-trigger` for mousedown-driven dropdowns.

## Shell & Command Reliability
1. **[2026-06-08] SQLite test DB cannot handle parallel test commands**
   Do instead: run `php artisan test` commands sequentially because `.env.testing` points to the shared `database/database.sqlite`; if locks corrupt schema state, back up and recreate that untracked file before rerunning.

## Domain Behavior Guardrails
1. **[2026-06-07] Filament dropdowns need an explicit layer scale**
   Do instead: keep `.fi-ta` as `overflow: visible`, keep content dropdowns below sidebar/topbar flyouts, and use `Table::configureUsing(...->deferColumnManager(false))` so column toggles apply live.
2. **[2026-06-07] Filament overlays inside sections need active stacking**
   Do instead: when a DatePicker/Select/dropdown opens inside `.fi-section`, raise the active section and panel in `resources/css/filament/admin/theme.css`, then validate with Playwright `admin-panel-layout.spec.js`.
3. **[2026-06-07] Filament fixed modals must not sit under transform containers**
   Do instead: avoid `transform` and `will-change: transform` on `.filament-registration-shell` ancestors, or clear them after GSAP one-shot animations before opening FileUpload editors/modals.
4. **[2026-06-07] Campista view should read like a ficha, not a disabled form**
   Do instead: override the view page `content()` with a schema `View` component plus ficha CSS, append relation manager content, and keep `EditCampista` using the editable `CampistaForm` schema.
5. **[2026-06-06] Public registration flow is campista-first**
   Do instead: render `welcome`/`content-form` with `campista-form` on `/` and `/campista`; keep `/inscricao-equipe-trabalho` redirected away unless explicitly re-enabled.
6. **[2026-06-06] Mobile public page is app-like and form-first**
   Do instead: keep the mobile bottom bar active by section, hide it on desktop with explicit CSS, and order the Filament form before payment/instructions on mobile.
7. **[2026-06-06] Public page must have only document-level vertical scroll**
   Do instead: use `overflow-x-clip` for horizontal clipping; avoid `overflow-x-hidden` on `main` because it computes `overflow-y: auto` and creates a second vertical scrollbar.
8. **[2026-06-06] GSAP anchor scrolling conflicts with Tailwind `scroll-smooth`**
   Do instead: temporarily disable smooth CSS and drive scroll with a GSAP numeric tween plus `window.scrollTo()` on update.
9. **[2026-06-06] Public Filament forms need official CSS above legacy resets**
   Do instead: import Filament support/actions/forms/notifications/schemas CSS in `resources/css/app.css` and keep `output.css` in a `legacy` cascade layer before `components`.
10. **[2026-06-06] Mobile GSAP motion must not initialize during loader lock**
   Do instead: initialize ScrollTrigger-dependent motion after the loader removes `body.is-loading`, use native anchor scrolling on touch layouts, and avoid scrub/parallax on mobile.

## User Directives
1. **[2026-06-08] Public campista photo upload is upload-only**
   Do instead: keep the public `CampistaForm` avatar field without image editor, forced crop, or automatic square resizing; preserve only upload, preview, storage, and image validation.
2. **[2026-06-06] Public hero uses responsive image assets**
   Do instead: use `public/img/hero-mobile.png` as the mobile hero background and `public/img/hero-desktop.png` as the desktop hero background.
3. **[2026-06-06] Public page includes the camp video as ambient media**
   Do instead: render `public/img/barraca.mp4` muted, autoplaying, looping, playsinline, and without player controls.
4. **[2026-06-06] Public brand logo comes from `public/img/logo.png`**
   Do instead: use the provided PNG for header, hero, footer, and favicons; do not replace it with generated SVG artwork.
5. **[2026-06-06] Complete framework and security upgrades**
   Do instead: update Laravel, Filament, Composer dependencies, and npm dependencies together, then validate with audits and tests.
