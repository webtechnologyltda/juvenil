<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Support\Auth\ManualPasswordResetLink;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('allows authorized administrators to generate a password reset link without sending email', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrador');
    $targetUser = User::factory()->create();
    $targetUser->assignRole('Administrador');

    $this->actingAs($administrator);
    Mail::fake();
    Notification::fake();

    $resetUrl = null;

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->mountAction(TestAction::make('generatePasswordResetLink')->table($targetUser))
        ->assertActionDataSet(function (array $data) use (&$resetUrl, $targetUser): array {
            $resetUrl = $data['reset_url'] ?? null;

            expect($resetUrl)->toBeString();

            parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

            expect($query)
                ->email->toBe($targetUser->email)
                ->token->not->toBeEmpty()
                ->signature->not->toBeEmpty();

            return ['reset_url' => $resetUrl];
        });

    expect(DB::table('password_reset_tokens')->where('email', $targetUser->email)->exists())
        ->toBeTrue();

    auth()->logout();

    $this->get($resetUrl)->assertSuccessful();

    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('hides password reset link generation from users without permission to update users', function () {
    $this->seed(ShieldSeeder::class);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo('view_any_user');
    $targetUser = User::factory()->create();
    $targetUser->assignRole('Administrador');

    $this->actingAs($viewer);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('generatePasswordResetLink')->table($targetUser));
});

it('disables password reset link generation until the user has panel access', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrador');
    $targetUser = User::factory()->create();

    $this->actingAs($administrator);

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionDisabled(TestAction::make('generatePasswordResetLink')->table($targetUser));
});

it('requires a panel access profile when creating a user', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrador');

    $this->actingAs($administrator);

    Livewire::test(CreateUser::class)
        ->assertFormFieldExists('roles')
        ->fillForm([
            'name' => 'Usuário sem perfil',
            'email' => 'sem-perfil@example.com',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
            'roles' => [],
        ])
        ->call('create')
        ->assertHasFormErrors([
            'roles' => 'required',
        ]);
});

it('saves the selected panel access profile when creating a user', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrador');
    $role = Role::findByName('Administrador');

    $this->actingAs($administrator);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Usuário com perfil',
            'email' => 'com-perfil@example.com',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
            'roles' => [$role->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'com-perfil@example.com')->firstOrFail()->hasRole($role))
        ->toBeTrue();
});

it('resets the password of a registered user without a panel role through a generated one-time link', function () {
    $this->seed(ShieldSeeder::class);

    $administrator = User::factory()->create();
    $administrator->assignRole('Super Administrador');
    $targetUser = User::factory()->create([
        'password' => 'SenhaAntiga123!',
    ]);

    $this->actingAs($administrator);

    $resetUrl = app(ManualPasswordResetLink::class)->generate($targetUser);

    parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

    auth()->logout();
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ResetPassword::class, [
        'email' => $query['email'],
        'token' => $query['token'],
    ])
        ->fillForm([
            'email' => $query['email'],
            'password' => 'NovaSenha123!',
            'passwordConfirmation' => 'NovaSenha123!',
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors()
        ->assertNotified(__('passwords.reset'))
        ->assertNotNotified(__('passwords.user'));

    expect(Hash::check('NovaSenha123!', $targetUser->fresh()->password))
        ->toBeTrue()
        ->and(DB::table('password_reset_tokens')->where('email', $targetUser->email)->exists())
        ->toBeFalse()
        ->and($targetUser->fresh()->canAccessPanel(Filament::getPanel('admin')))
        ->toBeFalse();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $targetUser->email,
            'password' => 'NovaSenha123!',
            'remember' => false,
        ])
        ->call('authenticate')
        ->assertHasErrors([
            'data.email' => 'A senha está correta, mas este usuário não possui um perfil de acesso ao painel. Solicite a um administrador que atribua um perfil.',
        ]);
});

it('allows login with the password defined through a manual reset link', function () {
    $this->seed(ShieldSeeder::class);

    $targetUser = User::factory()->create([
        'password' => 'SenhaAntiga123!',
    ]);
    $targetUser->assignRole('Administrador');

    $resetUrl = app(ManualPasswordResetLink::class)->generate($targetUser);

    parse_str((string) parse_url($resetUrl, PHP_URL_QUERY), $query);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ResetPassword::class, [
        'email' => $query['email'],
        'token' => $query['token'],
    ])
        ->fillForm([
            'email' => $query['email'],
            'password' => 'NovaSenha123!',
            'passwordConfirmation' => 'NovaSenha123!',
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $targetUser->email,
            'password' => 'NovaSenha123!',
            'remember' => false,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth()->id())->toBe($targetUser->id);
});

it('keeps the generic credentials error when the password is invalid', function () {
    $targetUser = User::factory()->create([
        'password' => 'SenhaCorreta123!',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $targetUser->email,
            'password' => 'SenhaIncorreta123!',
            'remember' => false,
        ])
        ->call('authenticate')
        ->assertHasErrors([
            'data.email' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
});

it('rejects an invalid password reset token for a registered user without a panel role', function () {
    $targetUser = User::factory()->create([
        'password' => 'SenhaAntiga123!',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::test(ResetPassword::class, [
        'email' => $targetUser->email,
        'token' => 'token-invalido',
    ])
        ->fillForm([
            'email' => $targetUser->email,
            'password' => 'NovaSenha123!',
            'passwordConfirmation' => 'NovaSenha123!',
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors()
        ->assertNotified(__('passwords.token'));

    expect(Hash::check('SenhaAntiga123!', $targetUser->fresh()->password))
        ->toBeTrue();
});

it('invalidates the previous password reset link when generating a new one', function () {
    $targetUser = User::factory()->create();

    $firstUrl = app(ManualPasswordResetLink::class)->generate($targetUser);
    $secondUrl = app(ManualPasswordResetLink::class)->generate($targetUser);

    parse_str((string) parse_url($firstUrl, PHP_URL_QUERY), $firstQuery);
    parse_str((string) parse_url($secondUrl, PHP_URL_QUERY), $secondQuery);

    $broker = Password::broker('users');

    expect($broker->tokenExists($targetUser, $firstQuery['token']))
        ->toBeFalse()
        ->and($broker->tokenExists($targetUser, $secondQuery['token']))
        ->toBeTrue();
});

it('shows instructions instead of an email password reset request form', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $requestUrl = Filament::getPanel('admin')->getRequestPasswordResetUrl();

    expect($requestUrl)->not->toBeNull();

    $this->get($requestUrl)
        ->assertSuccessful()
        ->assertSee('não envia links de redefinição por e-mail')
        ->assertSee('administrador');

    $user = User::factory()->create();

    Mail::fake();

    Livewire::test(RequestPasswordReset::class)
        ->set('data.email', $user->email)
        ->call('request');

    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())
        ->toBeFalse();

    Mail::assertNothingSent();
});
