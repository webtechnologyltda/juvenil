# Napkin Runbook

## Curation Rules
- Re-prioritize on every read.
- Keep recurring, high-value notes only.
- Max 10 items per category.
- Each item includes date + "Do instead".

## Execution & Validation (Highest Priority)
1. **[2026-06-06] Public Livewire registration pages need settings rows in tests**
   Do instead: use `RefreshDatabase`, disable Vite, and seed/update `general` settings before asserting route rendering.

## Shell & Command Reliability
1. **[2026-06-06] No repo-specific shell rule yet**
   Do instead: prefer repo-native commands discovered from Composer, npm, and docs.

## Domain Behavior Guardrails
1. **[2026-06-06] Public registration flow is campista-first**
   Do instead: render `welcome`/`content-form` with `campista-form` on `/` and `/campista`; keep `/inscricao-equipe-trabalho` redirected away unless explicitly re-enabled.
2. **[2026-06-06] Public Filament forms need official CSS above legacy resets**
   Do instead: import Filament support/actions/forms/notifications/schemas CSS in `resources/css/app.css` and keep `output.css` in a `legacy` cascade layer before `components`.
3. **[2026-06-06] Filament 5 schema components moved namespaces**
   Do instead: import layout/action containers like `Section`, `Grid`, `Tabs`, `Fieldset`, and `Actions` from `Filament\Schemas\Components`.
4. **[2026-06-06] Filament 5 form callbacks use schema utilities**
   Do instead: type form callback `Get`/`Set` parameters as `Filament\Schemas\Components\Utilities\Get` and `Set`.
5. **[2026-06-06] Filament 5 FileUpload removed old helper methods**
   Do instead: remove stale `optimize()`, `resize()`, and `uploadingMessage()` calls; use supported image/file upload methods from the installed package.

## User Directives
1. **[2026-06-06] Complete framework and security upgrades**
   Do instead: update Laravel, Filament, Composer dependencies, and npm dependencies together, then validate with audits and tests.
