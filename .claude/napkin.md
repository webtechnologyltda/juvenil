# Napkin Runbook

## Curation Rules
- Re-prioritize on every read.
- Keep recurring, high-value notes only.
- Max 10 items per category.
- Each item includes date + "Do instead".

## Execution & Validation (Highest Priority)
1. **[2026-06-06] Public Livewire registration pages need settings rows in tests**
   Do instead: use `RefreshDatabase`, disable Vite, and seed/update `general` settings before asserting route rendering.
2. **[2026-06-06] Browser checks may run through Vite dev assets**
   Do instead: check `public/hot` and the loaded script URLs before trusting production-build screenshots.
3. **[2026-06-07] Authenticated Filament layout needs real-login browser checks**
   Do instead: use Playwright to log in, assert admin cookie consent is hidden, verify document/table overflow, and capture dashboard/list screenshots.

## Shell & Command Reliability
1. **[2026-06-06] No repo-specific shell rule yet**
   Do instead: prefer repo-native commands discovered from Composer, npm, and docs.

## Domain Behavior Guardrails
1. **[2026-06-07] Filament fixed modals must not sit under transform containers**
   Do instead: avoid `transform` and `will-change: transform` on `.filament-registration-shell` ancestors, or clear them after GSAP one-shot animations before opening FileUpload editors/modals.
2. **[2026-06-06] Public registration flow is campista-first**
   Do instead: render `welcome`/`content-form` with `campista-form` on `/` and `/campista`; keep `/inscricao-equipe-trabalho` redirected away unless explicitly re-enabled.
3. **[2026-06-06] Mobile public page is app-like and form-first**
   Do instead: keep the mobile bottom bar active by section, hide it on desktop with explicit CSS, and order the Filament form before payment/instructions on mobile.
4. **[2026-06-06] Public page must have only document-level vertical scroll**
   Do instead: use `overflow-x-clip` for horizontal clipping; avoid `overflow-x-hidden` on `main` because it computes `overflow-y: auto` and creates a second vertical scrollbar.
5. **[2026-06-06] GSAP anchor scrolling conflicts with Tailwind `scroll-smooth`**
   Do instead: temporarily disable smooth CSS and drive scroll with a GSAP numeric tween plus `window.scrollTo()` on update.
6. **[2026-06-06] Public Filament forms need official CSS above legacy resets**
   Do instead: import Filament support/actions/forms/notifications/schemas CSS in `resources/css/app.css` and keep `output.css` in a `legacy` cascade layer before `components`.
7. **[2026-06-06] Mobile GSAP motion must not initialize during loader lock**
   Do instead: initialize ScrollTrigger-dependent motion after the loader removes `body.is-loading`, use native anchor scrolling on touch layouts, and avoid scrub/parallax on mobile.
8. **[2026-06-06] Public Filament primary color has three sources**
   Do instead: keep `FilamentColor::register()`, `tailwind.config.js` primary palette, and `.filament-registration-shell` `--primary-*`/input overrides aligned to the same brand color.
9. **[2026-06-06] Filament 5 schema components moved namespaces**
   Do instead: import layout/action containers like `Section`, `Grid`, `Tabs`, `Fieldset`, and `Actions` from `Filament\Schemas\Components`.
10. **[2026-06-06] Filament 5 FileUpload removed old helper methods**
   Do instead: remove stale `optimize()`, `resize()`, and `uploadingMessage()` calls; use supported image/file upload methods from the installed package.

## User Directives
1. **[2026-06-06] Public hero uses responsive image assets**
   Do instead: use `public/img/hero-mobile.png` as the mobile hero background and `public/img/hero-desktop.png` as the desktop hero background.
2. **[2026-06-06] Public page includes the camp video as ambient media**
   Do instead: render `public/img/barraca.mp4` muted, autoplaying, looping, playsinline, and without player controls.
3. **[2026-06-06] Public brand logo comes from `public/img/logo.png`**
   Do instead: use the provided PNG for header, hero, footer, and favicons; do not replace it with generated SVG artwork.
4. **[2026-06-06] Complete framework and security upgrades**
   Do instead: update Laravel, Filament, Composer dependencies, and npm dependencies together, then validate with audits and tests.
