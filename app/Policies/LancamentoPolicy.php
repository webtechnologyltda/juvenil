<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Lancamento;
use Illuminate\Auth\Access\HandlesAuthorization;

class LancamentoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_lancamento');
    }

    public function view(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('view_lancamento');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_lancamento');
    }

    public function update(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('update_lancamento');
    }

    public function delete(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('delete_lancamento');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_lancamento');
    }

    public function restore(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('restore_lancamento');
    }

    public function forceDelete(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('force_delete_lancamento');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_lancamento');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_lancamento');
    }

    public function replicate(AuthUser $authUser, Lancamento $lancamento): bool
    {
        return $authUser->can('replicate_lancamento');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_lancamento');
    }

}
