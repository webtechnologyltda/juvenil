<?php

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Filament\Resources\EquipeTrabalhoResource\Pages\CreateEquipeTrabalho;
use App\Filament\Resources\EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget;
use App\Models\EquipeTrabalho;
use App\Models\User;
use Database\Seeders\EquipeTrabalhoRosterSeeder;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('seeds the fixed PDF roster with members linked to their equipes', function () {
    Queue::fake();

    $this->seed(EquipeTrabalhoRosterSeeder::class);

    expect(EquipeTrabalhoRosterSeeder::totalMembers())->toBe(160)
        ->and(EquipeTrabalhoRosterSeeder::totalTeams())->toBe(14)
        ->and(EquipeTrabalho::query()->count())->toBe(160)
        ->and(EquipeTrabalho::query()->distinct('descricao')->count('descricao'))->toBe(14)
        ->and(EquipeTrabalho::query()
            ->where('descricao', 'Servos')
            ->pluck('nome')
            ->all())
        ->toContain('Thaise Berkenbrock Inacio', 'Bruno de Mello Sabino (Mega)')
        ->and(EquipeTrabalho::query()
            ->where('descricao', 'Missão')
            ->pluck('nome')
            ->all())
        ->toContain('Graziele de Oliveira Andriani', 'Zoraide da Silva')
        ->and(EquipeTrabalho::query()
            ->where('nome', 'Jean José Bento')
            ->firstOrFail())
        ->descricao->toBe('Cozinha')
        ->status->toBe(StatusInscricaoEquipeTrabalho::Aprovado)
        ->data_form->toBe([]);

    Queue::assertNothingPushed();
});

it('can rerun the fixed roster seeder without duplicating members', function () {
    $this->seed(EquipeTrabalhoRosterSeeder::class);
    $this->seed(EquipeTrabalhoRosterSeeder::class);

    expect(EquipeTrabalho::query()->count())->toBe(160);
});

it('does not keep an upload import action on the equipe de trabalho list page', function () {
    $page = file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/Pages/ListEquipeTrabalhos.php'));

    expect($page)
        ->not->toContain('FileUpload::make')
        ->not->toContain('importarEquipeTrabalho')
        ->not->toContain('EquipeTrabalhoRosterImport')
        ->toContain('Actions\\CreateAction::make()');
});

it('creates an admin work team registration with only the name required', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);
    Queue::fake();

    Livewire::test(CreateEquipeTrabalho::class)
        ->fillForm([
            'nome' => 'Servo Importado Manualmente',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $registration = EquipeTrabalho::query()
        ->where('nome', 'Servo Importado Manualmente')
        ->firstOrFail();

    expect($registration)
        ->data_form->toBe([])
        ->status->toBe(StatusInscricaoEquipeTrabalho::Aprovado);
});

it('renders equipe de trabalho stats for roster records without sex data', function () {
    $this->seed(EquipeTrabalhoRosterSeeder::class);

    Livewire::test(EquipeTrabalhoStatsWidget::class)
        ->assertOk()
        ->assertSee('Qtd de Inscrições')
        ->assertSee('Qtd Mulheres')
        ->assertSee('Qtd Homens');
});
