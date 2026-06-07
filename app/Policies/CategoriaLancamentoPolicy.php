<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CategoriaLancamento;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoriaLancamentoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_categoria_lancamento');
    }

    public function view(AuthUser $authUser, CategoriaLancamento $categoriaLancamento): bool
    {
        return $authUser->can('view_categoria_lancamento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_categoria_lancamento');
    }

    public function update(AuthUser $authUser, CategoriaLancamento $categoriaLancamento): bool
    {
        return $authUser->can('update_categoria_lancamento');
    }

    public function delete(AuthUser $authUser, CategoriaLancamento $categoriaLancamento): bool
    {
        return $authUser->can('delete_categoria_lancamento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_categoria_lancamento');
    }
}
