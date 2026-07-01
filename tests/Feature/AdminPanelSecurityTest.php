<?php

use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('restricts admin panel access to users with an assigned role', function () {
    $this->seed(ShieldSeeder::class);

    $panel = Filament::getPanel('admin');
    $userWithoutRole = User::factory()->create();
    $userWithDirectPermission = User::factory()->create();
    $admin = User::factory()->create();
    Permission::findOrCreate('view_any_campista');
    $userWithDirectPermission->givePermissionTo('view_any_campista');
    $admin->assignRole('Super Administrador');

    expect($userWithoutRole->canAccessPanel($panel))
        ->toBeFalse()
        ->and($userWithDirectPermission->canAccessPanel($panel))
        ->toBeTrue()
        ->and($admin->canAccessPanel($panel))
        ->toBeTrue();
});

it('keeps Filament upload path tampering protection enabled globally', function () {
    $provider = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));

    expect($provider)
        ->toContain('FileUpload::configureUsing')
        ->toContain('->preventFilePathTampering()')
        ->toContain('RichEditor::configureUsing')
        ->toContain('->preventFileAttachmentPathTampering()');
});

it('keeps inline editable columns guarded by update authorization', function () {
    $campistaTable = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaTable.php'));
    $equipeTrabalhoTable = file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoTable.php'));

    expect($campistaTable)
        ->toContain("SelectColumn::make('tribo_id')")
        ->toContain("->disabled(fn (Campista \$record): bool => ! auth()->user()?->can('update', \$record))")
        ->and($equipeTrabalhoTable)
        ->toContain("SelectColumn::make('status')")
        ->toContain("->disabled(fn (EquipeTrabalho \$record): bool => ! auth()->user()?->can('update', \$record))");
});

it('sanitizes configured public registration block html before rendering it raw', function () {
    $view = file_get_contents(resource_path('views/livewire/campista-form.blade.php'));

    expect($view)
        ->toContain("str(\$this->settings['liberacao_inscricoes_bloco'])->sanitizeHtml()")
        ->not->toContain("{!! \$this->settings['liberacao_inscricoes_bloco'] !!}");
});

it('restricts arbitrary Livewire uploads on public Filament form components', function () {
    $publicComponents = [
        file_get_contents(app_path('Livewire/CampistaForm.php')),
        file_get_contents(app_path('Livewire/EquipeTrabalhoForm.php')),
        file_get_contents(app_path('Livewire/CampistaWaitlistForm.php')),
    ];

    foreach ($publicComponents as $component) {
        expect($component)
            ->toContain('use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;')
            ->toContain('use RestrictsFileUploadsToSchemaComponents;');
    }
});

it('hides waitlist invitation tokens from model serialization', function () {
    $model = file_get_contents(app_path('Models/WaitlistEntry.php'));

    expect($model)
        ->toContain('protected $hidden = [')
        ->toContain("'invitation_token_hash'")
        ->toContain("'invitation_token_encrypted'");
});
