<?php

use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\CampistaResource\Pages\ListCampistas;
use App\Filament\Resources\EquipeTrabalhoResource\Pages\ListEquipeTrabalhos;
use App\Filament\Resources\LancamentoResource\Pages\ListLancamentos;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\Financeiro\FinancialFilterOptions;
use Database\Seeders\ShieldSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user);
});

it('filters campista registrations by payment status and financial category', function (): void {
    $paidCategory = registrationFilterCategory('Categoria paga', '#123456', 'heroicon-o-star');
    $pendingCategory = registrationFilterCategory('Categoria pendente', '#abcdef', 'heroicon-o-clock');
    $paidCampista = Campista::factory()->create([
        'nome' => 'Campista pago',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $pendingCampista = Campista::factory()->create([
        'nome' => 'Campista pendente',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    registrationFilterLaunch($paidCampista, $paidCategory, StatusLacamento::Pago);
    registrationFilterLaunch($pendingCampista, $pendingCategory, StatusLacamento::Pendente);

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->assertTableFilterExists('payment_status')
        ->assertTableFilterExists('categoria_lancamento_id')
        ->filterTable('payment_status', [StatusLacamento::Pago->value])
        ->assertCanSeeTableRecords([$paidCampista])
        ->assertCanNotSeeTableRecords([$pendingCampista]);

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->filterTable('categoria_lancamento_id', [$pendingCategory->getKey()])
        ->assertCanSeeTableRecords([$pendingCampista])
        ->assertCanNotSeeTableRecords([$paidCampista]);
});

it('filters team registrations by payment status and financial category', function (): void {
    $paidCategory = registrationFilterCategory('Equipe paga', '#334455', 'heroicon-o-banknotes');
    $pendingCategory = registrationFilterCategory('Equipe pendente', '#bbccdd', 'heroicon-o-clock');
    $paidRegistration = EquipeTrabalho::factory()->create(['nome' => 'Equipe paga']);
    $pendingRegistration = EquipeTrabalho::factory()->create(['nome' => 'Equipe pendente']);

    registrationFilterLaunch($paidRegistration, $paidCategory, StatusLacamento::Pago);
    registrationFilterLaunch($pendingRegistration, $pendingCategory, StatusLacamento::Pendente);

    Livewire::test(ListEquipeTrabalhos::class)
        ->assertTableFilterExists('payment_status')
        ->assertTableFilterExists('categoria_lancamento_id')
        ->filterTable('payment_status', [StatusLacamento::Pago->value])
        ->assertCanSeeTableRecords([$paidRegistration])
        ->assertCanNotSeeTableRecords([$pendingRegistration]);

    Livewire::test(ListEquipeTrabalhos::class)
        ->filterTable('categoria_lancamento_id', [$pendingCategory->getKey()])
        ->assertCanSeeTableRecords([$pendingRegistration])
        ->assertCanNotSeeTableRecords([$paidRegistration]);
});

it('filters financial entries by payment status', function (): void {
    $category = registrationFilterCategory('Inscrições', '#f46b12', 'heroicon-o-ticket');
    $paidRegistration = Campista::factory()->create([
        'nome' => 'Pagamento confirmado',
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $pendingRegistration = Campista::factory()->create([
        'nome' => 'Pagamento pendente',
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $paidLaunch = registrationFilterLaunch($paidRegistration, $category, StatusLacamento::Pago);
    $pendingLaunch = registrationFilterLaunch($pendingRegistration, $category, StatusLacamento::Pendente);

    Livewire::test(ListLancamentos::class)
        ->assertTableFilterExists('status')
        ->filterTable('status', [StatusLacamento::Pago->value])
        ->assertCanSeeTableRecords([$paidLaunch])
        ->assertCanNotSeeTableRecords([$pendingLaunch]);
});

it('renders category filter options with the configured icon color and name', function (): void {
    $category = registrationFilterCategory('Categoria visual', '#123456', 'heroicon-o-star');

    $option = FinancialFilterOptions::categories()[$category->getKey()];

    expect($option)
        ->toContain('Categoria visual')
        ->toContain('data-icon="heroicon-o-star"')
        ->toContain('background-color: #123456');
});

function registrationFilterCategory(string $name, string $color, string $icon): CategoriaLancamento
{
    return CategoriaLancamento::factory()->create([
        'nome' => $name,
        'tipo' => TipoLacamento::Receita->value,
        'cor' => $color,
        'icone' => $icon,
    ]);
}

function registrationFilterLaunch(
    Model $registration,
    CategoriaLancamento $category,
    StatusLacamento $status,
): Lancamento {
    $launch = Lancamento::factory()->create([
        'nome' => 'Pagamento '.$registration->getAttribute('nome'),
        'status' => $status->value,
        'tipo' => TipoLacamento::Receita->value,
        'comprovante' => [],
        'user_id' => null,
    ]);

    $launch->items()->create([
        'nome' => (string) $registration->getAttribute('nome'),
        'valor' => 25000,
        'categoria_lancamento_id' => $category->getKey(),
        'registration_type' => $registration::class,
        'registration_id' => $registration->getKey(),
    ]);

    return $launch;
}
