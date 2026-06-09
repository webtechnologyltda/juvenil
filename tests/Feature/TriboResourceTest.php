<?php

use App\Filament\Resources\TriboResource;
use App\Filament\Resources\TriboResource\Pages\EditTribo;
use App\Filament\Resources\TriboResource\RelationManagers\CampistasRelationManager;
use App\Filament\Resources\TriboResource\RelationManagers\EquipeTrabalhosRelationManager;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\Tribo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uses a color picker for the registered tribe color', function () {
    $resource = file_get_contents(app_path('Filament/Resources/TriboResource.php'));

    expect($resource)
        ->toContain('use Filament\\Forms\\Components\\ColorPicker;')
        ->toContain("ColorPicker::make('cor_hex')")
        ->toContain("TextInput::make('cor')")
        ->toContain("'lg' => 4")
        ->toContain("'lg' => 3")
        ->toContain("'lg' => 1")
        ->toContain('TribeColor::resolve')
        ->toContain('->html()');
});

it('registers campista and equipe de trabalho relation managers on tribes', function () {
    expect(TriboResource::getRelations())
        ->toContain(CampistasRelationManager::class)
        ->toContain(EquipeTrabalhosRelationManager::class);
});

it('stores the custom color on tribes', function () {
    $tribe = Tribo::query()->create([
        'cor' => 'Azul',
        'cor_hex' => '#123abc',
    ]);

    expect($tribe->refresh()->cor_hex)->toBe('#123abc');
});

it('shows only the campistas and servos linked to the selected tribe', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $tribe = Tribo::factory()->create([
        'cor' => 'Verde',
        'cor_hex' => '#16a34a',
    ]);
    $otherTribe = Tribo::factory()->create([
        'cor' => 'Azul',
        'cor_hex' => '#2563eb',
    ]);

    $campista = Campista::factory()->create([
        'nome' => 'Campista da Tribo Verde',
        'tribo_id' => $tribe->id,
        'user_id' => null,
    ]);
    $otherCampista = Campista::factory()->create([
        'nome' => 'Campista da Tribo Azul',
        'tribo_id' => $otherTribe->id,
        'user_id' => null,
    ]);

    $servo = EquipeTrabalho::factory()->create([
        'nome' => 'Servo da Tribo Verde',
        'tribo_id' => $tribe->id,
    ]);
    $otherServo = EquipeTrabalho::factory()->create([
        'nome' => 'Servo da Tribo Azul',
        'tribo_id' => $otherTribe->id,
    ]);

    expect($tribe->campistas()->pluck('id')->all())
        ->toBe([$campista->id])
        ->and($tribe->equipeTrabalhos()->pluck('id')->all())
        ->toBe([$servo->id]);

    Livewire::test(CampistasRelationManager::class, [
        'ownerRecord' => $tribe,
        'pageClass' => EditTribo::class,
    ])
        ->assertCanSeeTableRecords([$campista])
        ->assertCanNotSeeTableRecords([$otherCampista])
        ->assertSee('Campista da Tribo Verde')
        ->assertDontSee('Campista da Tribo Azul');

    Livewire::test(EquipeTrabalhosRelationManager::class, [
        'ownerRecord' => $tribe,
        'pageClass' => EditTribo::class,
    ])
        ->assertCanSeeTableRecords([$servo])
        ->assertCanNotSeeTableRecords([$otherServo])
        ->assertSee('Servo da Tribo Verde')
        ->assertDontSee('Servo da Tribo Azul');
});
