# Napkin Runbook

## Curation Rules
- Re-prioritize on every read.
- Keep recurring, high-value notes only.
- Max 10 items per category.
- Each item includes date + "Do instead".

## Execution & Validation (Highest Priority)
1. **[2026-06-09] Large report previews need a real web request check**
   Do instead: after admin report preview changes, test the unfiltered route through `juvenil.test` or Playwright and confirm the expected response type; focused small-record tests can miss web runtime failures.
2. **[2026-06-06] Public Livewire registration pages need settings rows in tests**
   Do instead: use `RefreshDatabase`, disable Vite, and seed/update `general` settings before asserting route rendering.
3. **[2026-06-07] Filament package translations use Laravel vendor overrides**
   Do instead: add package keys under `lang/vendor/{package}/pt_BR/*.php` and validate with `php artisan tinker --execute='app()->setLocale("pt_BR"); echo __("package::file.key");'`.
4. **[2026-06-07] Filament admin table theming is global CSS**
   Do instead: style `.fi-ta-*` selectors in `resources/css/filament/admin/theme.css`, then validate with `npm run build`, focused PHP tests, and `tests/Browser/admin-panel-layout.spec.js`.
5. **[2026-06-06] Browser checks may run through Vite dev assets**
   Do instead: check `public/hot` and the loaded script URLs before trusting production-build screenshots.
6. **[2026-06-07] Authenticated Filament layout needs real-login browser checks**
   Do instead: use Playwright to log in, assert admin cookie consent is hidden, verify document/table overflow, and capture dashboard/list screenshots.
7. **[2026-06-07] Filament dropdown tests need client readiness**
   Do instead: wait for `window.Alpine && window.Livewire`, then interact with `.fi-dropdown-trigger` for mousedown-driven dropdowns.

## Shell & Command Reliability
1. **[2026-06-08] SQLite test DB cannot handle parallel test commands**
   Do instead: run `php artisan test` commands sequentially because `.env.testing` points to the shared `database/database.sqlite`; if locks corrupt schema state, back up and recreate that untracked file before rerunning.

## Domain Behavior Guardrails
1. **[2026-06-23] EquipeTrabalho public and admin forms share helpers**
   Do instead: keep `EquipeTrabalhoForm::getFormCreate()` as the full public registration form and add/use explicit admin form methods for simplified admin create/edit flows.
2. **[2026-06-07] Filament dropdowns need an explicit layer scale**
   Do instead: keep `.fi-ta` as `overflow: visible`, keep content dropdowns below sidebar/topbar flyouts, and use `Table::configureUsing(...->deferColumnManager(false))` so column toggles apply live.
3. **[2026-06-07] Filament overlays inside sections need active stacking**
   Do instead: when a DatePicker/Select/dropdown opens inside `.fi-section`, raise the active section and panel in `resources/css/filament/admin/theme.css`, then validate with Playwright `admin-panel-layout.spec.js`.
4. **[2026-06-07] Filament fixed modals must not sit under transform containers**
   Do instead: avoid `transform` and `will-change: transform` on `.filament-registration-shell` ancestors, or clear them after GSAP one-shot animations before opening FileUpload editors/modals.
5. **[2026-06-07] Campista view should read like a ficha, not a disabled form**
   Do instead: override the view page `content()` with a schema `View` component plus ficha CSS, append relation manager content, and keep `EditCampista` using the editable `CampistaForm` schema.
6. **[2026-06-06] Public registration flow is campista-first**
   Do instead: render `welcome`/`content-form` with `campista-form` on `/` and `/campista`; keep `/inscricao-equipe-trabalho` redirected away unless explicitly re-enabled.
7. **[2026-06-06] Mobile public page is app-like and form-first**
   Do instead: keep the mobile bottom bar active by section, hide it on desktop with explicit CSS, and order the Filament form before payment/instructions on mobile.
8. **[2026-06-06] Public page must have only document-level vertical scroll**
   Do instead: use `overflow-x-clip` for horizontal clipping; avoid `overflow-x-hidden` on `main` because it computes `overflow-y: auto` and creates a second vertical scrollbar.
9. **[2026-06-06] GSAP anchor scrolling conflicts with Tailwind `scroll-smooth`**
   Do instead: temporarily disable smooth CSS and drive scroll with a GSAP numeric tween plus `window.scrollTo()` on update.
10. **[2026-06-06] Public Filament forms need official CSS above legacy resets**
   Do instead: import Filament support/actions/forms/notifications/schemas CSS in `resources/css/app.css` and keep `output.css` in a `legacy` cascade layer before `components`.

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
