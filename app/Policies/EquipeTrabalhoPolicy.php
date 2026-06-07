<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\EquipeTrabalho;
use Illuminate\Auth\Access\HandlesAuthorization;

class EquipeTrabalhoPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_equipe_trabalho');
    }

    public function view(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('view_equipe_trabalho');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_equipe_trabalho');
    }

    public function update(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('update_equipe_trabalho');
    }

    public function delete(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('delete_equipe_trabalho');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_equipe_trabalho');
    }

    public function restore(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('restore_equipe_trabalho');
    }

    public function forceDelete(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('force_delete_equipe_trabalho');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_equipe_trabalho');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_equipe_trabalho');
    }

    public function replicate(AuthUser $authUser, EquipeTrabalho $equipeTrabalho): bool
    {
        return $authUser->can('replicate_equipe_trabalho');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_equipe_trabalho');
    }

}
