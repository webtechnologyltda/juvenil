<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('renders a readable financial launch view page', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrições do Acampamento',
        'tipo' => TipoLacamento::Receita,
        'cor' => '#f46b12',
        'icone' => 'heroicon-o-ticket',
        'ativo' => true,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Recebimento inscrição João View',
        'descricao' => null,
        'comprador' => null,
        'data' => '2026-07-04 10:15:00',
        'valor' => 123456,
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'comprovante' => [
            [
                'type' => 'anexar_comprovante',
                'data' => [
                    'url' => ['comprovantes/lancamento-joao-view.pdf'],
                    'observacao' => 'Comprovante enviado pelo financeiro',
                ],
            ],
        ],
        'batch_code' => 'LOTE-20260608-001',
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Parcela única João View',
        'descricao' => 'Item de inscrição vinculado à categoria',
        'valor' => 123456,
        'categoria_lancamento_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee("Lançamento #{$lancamento->id}")
        ->assertSee('Recebimento inscrição João View')
        ->assertSee('R$ 1.234,56')
        ->assertSee('Pago')
        ->assertSee('Pix')
        ->assertSee('Receita')
        ->assertSee('Inscrições do Acampamento')
        ->assertSee('Parcela única João View')
        ->assertSee('Item de inscrição vinculado à categoria')
        ->assertSee('04/07/2026')
        ->assertSee('LOTE-20260608-001')
        ->assertSee('lancamento-joao-view.pdf')
        ->assertSee('Comprovante enviado pelo financeiro')
        ->assertSee('Sem descrição informada');
});

it('configures the launch table to open view pages by default while keeping edit as an explicit action', function () {
    $resource = file_get_contents(app_path('Filament/Resources/LancamentoResource.php'));

    expect($resource)
        ->toContain("'view' => Pages\ViewLancamento::route('/{record}')")
        ->toContain('->recordUrl(')
        ->toContain("static::getUrl('view'")
        ->toContain('->recordActions(')
        ->toContain('EditAction::make()');
});

it('renders receipt documents as preview cards for images and pdfs', function () {
    $this->seed(ShieldSeeder::class);
    Storage::fake((string) config('filament.default_filesystem_disk', 'local'));

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    Storage::disk((string) config('filament.default_filesystem_disk', 'local'))->put('comprovantes/pix-imagem-preview.png', 'image');
    Storage::disk((string) config('filament.default_filesystem_disk', 'local'))->put('comprovantes/recibo-preview.pdf', 'pdf');

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Lançamento com comprovantes visuais',
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'comprovante' => [
            [
                'type' => 'anexar_comprovante',
                'data' => [
                    'url' => [
                        'comprovantes/pix-imagem-preview.png',
                        'comprovantes/recibo-preview.pdf',
                    ],
                    'observacao' => 'PIX e recibo conferidos',
                ],
            ],
        ],
        'user_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('juvenil-lancamento-receipts', false)
        ->assertSee('juvenil-lancamento-receipt-card--image', false)
        ->assertSee('juvenil-lancamento-receipt-card--pdf', false)
        ->assertSee('pix-imagem-preview.png')
        ->assertSee('recibo-preview.pdf')
        ->assertSee('/admin/lancamentos/'.$lancamento->id.'/comprovante', false)
        ->assertSee('path=comprovantes%2Fpix-imagem-preview.png', false)
        ->assertSee('path=comprovantes%2Frecibo-preview.pdf', false)
        ->assertDontSee('/storage/comprovantes/pix-imagem-preview.png', false)
        ->assertSee('<img', false)
        ->assertSee('<iframe', false)
        ->assertSee('PIX e recibo conferidos');

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('admin.lancamentos.comprovantes.show', now()->addMinutes(5), [
            'lancamento' => $lancamento,
            'path' => 'comprovantes/recibo-preview.pdf',
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    Storage::disk((string) config('filament.default_filesystem_disk', 'local'))->put('comprovantes/fora-do-lancamento.pdf', 'pdf');

    $this->actingAs($user)
        ->get(URL::temporarySignedRoute('admin.lancamentos.comprovantes.show', now()->addMinutes(5), [
            'lancamento' => $lancamento,
            'path' => 'comprovantes/fora-do-lancamento.pdf',
        ]))
        ->assertNotFound();
});

it('links linked registrations from launch items', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $campista = Campista::factory()->create([
        'nome' => 'Vitória Costa 119',
    ]);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrição Vitória Costa',
        'tipo' => TipoLacamento::Receita,
        'ativo' => true,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Recebimento Vitória Costa',
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'comprovante' => [],
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Vitória Costa 119',
        'valor' => 35000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => Campista::class,
        'registration_id' => $campista->id,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('Campista #'.$campista->id.' - Vitória Costa 119')
        ->assertSee('Visualizar inscrição')
        ->assertSee(route('filament.admin.resources.campistas.view', ['record' => $campista]), false);
});

it('links team work registrations from launch items to a view page', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $member = EquipeTrabalho::factory()->create([
        'nome' => 'Servo Equipe View',
    ]);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Equipe View',
        'tipo' => TipoLacamento::Receita,
        'ativo' => true,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Recebimento Servo Equipe',
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'comprovante' => [],
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Servo Equipe View',
        'valor' => 35000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => EquipeTrabalho::class,
        'registration_id' => $member->id,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('EquipeTrabalho #'.$member->id.' - Servo Equipe View')
        ->assertSee(route('filament.admin.resources.equipe-trabalhos.view', ['record' => $member]), false);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.equipe-trabalhos.view', ['record' => $member]))
        ->assertOk()
        ->assertSee('Inscrição - Equipe #'.$member->id)
        ->assertSee('Servo Equipe View');
});

it('keeps raw legacy receipt paths visible on the launch view page', function () {
    $this->seed(ShieldSeeder::class);
    Storage::fake((string) config('filament.default_filesystem_disk', 'local'));

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    Storage::disk((string) config('filament.default_filesystem_disk', 'local'))->put('comprovantes/comprovante-legado.pdf', 'pdf');

    $lancamento = Lancamento::factory()->create([
        'nome' => 'Lançamento com comprovante legado',
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'comprovante' => [],
        'user_id' => null,
    ]);

    DB::table('lancamentos')
        ->where('id', $lancamento->id)
        ->update(['comprovante' => 'comprovantes/comprovante-legado.pdf']);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('comprovante-legado.pdf')
        ->assertSee('/admin/lancamentos/'.$lancamento->id.'/comprovante', false)
        ->assertSee('path=comprovantes%2Fcomprovante-legado.pdf', false)
        ->assertDontSee('/storage/comprovantes/comprovante-legado.pdf', false);
});
