<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== jeffersongoncalves/filament-cep-field rules ===

## Filament CEP Field

A Filament form field for Brazilian postal codes (CEP) with automatic mask formatting (99999-999), address lookup via external APIs (BrasilAPI, ViaCEP, AwesomeAPI), database caching, and auto-population of address fields. Requires Filament 5.0+ and PHP 8.2+.

### Installation

<code-snippet name="Install the plugin" lang="bash">
composer require jeffersongoncalves/filament-cep-field
</code-snippet>

### Publish & Run Migrations

<code-snippet name="Publish and run CEP cache migrations" lang="bash">
php artisan vendor:publish --tag=cep-migrations
php artisan migrate
</code-snippet>

### Basic Usage

<code-snippet name="Use CepInput in a form" lang="php">
use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;

CepInput::make('cep')
    ->required()
    ->setStreetField('street')
    ->setNeighborhoodField('neighborhood')
    ->setCityField('city')
    ->setStateField('state');
</code-snippet>

### Advanced Usage

<code-snippet name="CepInput with all options" lang="php">
use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;

CepInput::make('cep')
    ->required()
    ->setMode('suffix')
    ->setActionLabel('Buscar CEP')
    ->setActionLabelHidden(false)
    ->setErrorMessage('CEP nao encontrado.')
    ->setStreetField('street')
    ->setNeighborhoodField('neighborhood')
    ->setCityField('city')
    ->setStateField('state');
</code-snippet>

### Key Methods

- `setMode(string $mode)` - Button position: `'suffix'` (default) or `'prefix'`
- `setActionLabel(string $label)` - Custom search button label (default: `'Buscar CEP'`)
- `setActionLabelHidden(bool $hidden)` - Show/hide action label (default: `false`)
- `setErrorMessage(string $message)` - Custom error message (default: `'CEP invalido.'`)
- `setStreetField(string $field)` - Field to populate with street name (default: `'street'`)
- `setNeighborhoodField(string $field)` - Field to populate with neighborhood (default: `'neighborhood'`)
- `setCityField(string $field)` - Field to populate with city name (default: `'city'`)
- `setStateField(string $field)` - Field to populate with state (default: `'state'`)

### Architecture

- **Namespace**: `JeffersonGoncalves\Filament\CepField`
- **Component**: `CepInput` extends Filament form field
- **Service Provider**: `CepFieldServiceProvider` (auto-discovered)
- **Depends on**: `jeffersongoncalves/laravel-cep` for API and caching logic

### Best Practices

- Always publish and run the migrations for database-backed CEP caching
- Map field names to match your form schema (e.g., `setStreetField('endereco')` for Portuguese field names)
- The component auto-applies the Brazilian CEP mask (99999-999) -- no extra mask package needed
- Configure SSL certificates (`cacert.pem`) if you encounter HTTPS errors on Windows environments

=== webtechnologyltda/tracefy-sdk rules ===

## Tracefy SDK

Tracefy SDK is the official error observability layer for Laravel applications that install this package. It centralizes exception, fatal error, queue event, browser error, and performance signal delivery to the Tracefy platform.

### Package overview

- Use Tracefy SDK as the primary integration point for centralized error reporting.
- Prefer the published `config/tracefy-client.php` file for configuration. `config/tracefy-sdk.php` exists for compatibility, but new integrations should align with `tracefy-client`.
- The package auto-registers its Laravel service provider, captures backend exceptions through the Laravel exception handler, can report fatal shutdown errors, monitors queue lifecycle events, and publishes a standalone JavaScript tracker.

### What this SDK is responsible for

- Sending captured backend exceptions to `POST {TRACEFY_ENDPOINT}/api/events/exception`.
- Capturing queue lifecycle events and sending them to the queue event endpoint when enabled.
- Capturing browser `error` and `unhandledrejection` events through the published JS tracker.
- Including structured runtime, request, user, trace, query, and performance data when available.
- Sanitizing sensitive fields and headers before outbound delivery.

### Core integration principles

- Keep Tracefy integration centralized. Prefer the package's built-in hooks and service container bindings over ad-hoc duplicate reporters.
- Do not add multiple reporting paths for the same exception unless there is a clear need and duplicate delivery is controlled.
- Respect environment-aware behavior. Do not assume `local`, `testing`, `staging`, and `production` should all report identically.
- Preserve full exception context whenever possible: message, class, file, line, root cause, and stack trace.
- Use the package's published install flow and assets instead of recreating them manually.

<code-snippet name="Install Tracefy SDK Assets" lang="bash">
php artisan tracefy-sdk:install
</code-snippet>

<code-snippet name="Core Environment Configuration" lang="ini">
TRACEFY_ENABLED=true
TRACEFY_ENDPOINT=https://tracefy.webtechnology.com.br
TRACEFY_API_KEY=
TRACEFY_ENVIRONMENTS=production,staging
TRACEFY_RELEASE=1.0.0
</code-snippet>

### Best practices

- Prefer automatic exception capture through Laravel's exception pipeline before adding manual reporting logic.
- Publish and use the provided JS asset at `public/vendor/tracefy-sdk/tracefy-js-tracker.js` for browser-side capture.
- Keep payloads structured and compact. Send useful metadata, not raw application state dumps.
- Use authenticated user context only when it helps debugging and can be safely disclosed.
- Keep queue and performance instrumentation enabled only when it provides diagnostic value for the project.
- When extending the package, update this guideline if the package's permanent conventions change.

### Error reporting expectations

- Capture unexpected exceptions consistently and keep stack traces intact.
- Do not silently swallow exceptions after reporting them unless the application explicitly requires graceful recovery.
- Avoid duplicate logging and duplicate Tracefy delivery for the same failure path.
- Prefer structured context over free-form strings.
- Preserve the primary project frame so the most actionable file and line remain visible.
- Keep fatal error capture enabled when production observability depends on it.

<code-snippet name="Resolve The SDK From The Container" lang="php">
use Webtechnologyltda\TracefySdk\TracefySdk;

$tracefy = app(TracefySdk::class);
$latest = $tracefy->latestCapturedException();
</code-snippet>

### Custom context guidance

- Add context only when it improves debugging or correlation.
- Favor technical, bounded context such as authenticated user identifier or role when appropriate.
- Favor route name, route parameters, request path, and full URL when they help reproduce the issue.
- Favor application environment, release/version, request ID, or correlation ID for cross-system debugging.
- Favor sanitized request payload fragments, tags, origin markers, queue metadata, and diagnostic flags over raw dumps.
- Keep context related to the event being reported.
- Sanitize or omit secrets before they reach Tracefy.
- Prefer stable keys and predictable structures so downstream analysis remains useful.

When customizing user payloads, prefer the package contract instead of relying on broad model serialization:

<code-snippet name="Provide Safe User Context" lang="php">
use Illuminate\Foundation\Auth\User as Authenticatable;
use Webtechnologyltda\TracefySdk\Contracts\TracefyUserTrackable;

class User extends Authenticatable implements TracefyUserTrackable
{
    public function toTracefyUser(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
</code-snippet>

### Environment awareness

- Use `TRACEFY_ENABLED` to disable the SDK entirely when needed.
- Use `TRACEFY_ENVIRONMENTS` to control where outbound reporting is allowed.
- Be deliberate with testing and console behavior. The unhandled throwable hook is disabled in console and testing by default unless explicitly enabled.
- Set `TRACEFY_RELEASE` so events can be correlated with deployments.
- Treat production as the strictest environment for privacy, signal quality, and operational correctness.

### Performance monitoring guidance

- Tracefy performance data should help diagnose bottlenecks, not create noise.
- Prefer meaningful measurements such as request duration, peak memory, query count, and slow queries.
- Tune `TRACEFY_SLOW_QUERY_THRESHOLD_MS` to reflect the application's baseline.
- Do not emit arbitrary performance events that cannot drive action.
- Avoid excessive instrumentation in hot paths if it materially increases overhead.

### JavaScript/browser error tracking guidance

- Browser errors are part of the same centralized observability story and should be correlated with environment and page context.
- Use the published tracker instead of writing a parallel browser reporter unless there is a strong technical reason.
- Include only safe browser context. Do not expose secrets, tokens, full authorization headers, or sensitive personal data.
- Keep client-side tracking lightweight and avoid blocking navigation or degrading the user experience.

<code-snippet name="Initialize Browser Tracking" lang="html">
<script src="/vendor/tracefy-sdk/tracefy-js-tracker.js"></script>
<script>
  window.Tracefy.init({
    baseUrl: "https://tracefy.webtechnology.com.br",
    eventRoute: "/api/events",
    eventEndpoint: "js",
    apiKey: "your-api-key",
    environment: "production"
  });
</script>
</code-snippet>

### What AI agents must avoid

- Do not invent Tracefy APIs, facades, helpers, or config keys that are not present in this package.
- Do not replace Tracefy with ad-hoc parallel reporters when this SDK is already installed.
- Do not send secrets, passwords, tokens, cookies, authorization headers, session contents, or financial data without explicit sanitization.
- Do not dump entire request bodies, model graphs, or unbounded payloads into context.
- Do not capture the same error from multiple unnecessary layers.
- Do not disable sanitization or environment guards casually.
- Do not turn this guideline into task-specific implementation documentation; keep integrations simple, centralized, and maintainable.

=== filament/blueprint rules ===

## Filament Blueprint

You are writing Filament v5 implementation plans. Plans must be specific enough
that an implementing agent can write code without making decisions.

**Start here**: Read
`/vendor/filament/blueprint/resources/markdown/planning/overview.md` for plan format,
required sections, and what to clarify with the user before planning.

</laravel-boost-guidelines>
