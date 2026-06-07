<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tribo;
use Illuminate\Auth\Access\HandlesAuthorization;

class TriboPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_tribo');
    }

    public function view(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('view_tribo');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_tribo');
    }

    public function update(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('update_tribo');
    }

    public function delete(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('delete_tribo');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_tribo');
    }

    public function restore(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('restore_tribo');
    }

    public function forceDelete(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('force_delete_tribo');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_tribo');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_tribo');
    }

    public function replicate(AuthUser $authUser, Tribo $tribo): bool
    {
        return $authUser->can('replicate_tribo');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_tribo');
    }

    public function audit(AuthUser $authUser): bool
    {
        return $authUser->can('audit_tribo');
    }

    public function restoreAudit(AuthUser $authUser): bool
    {
        return $authUser->can('restoreAudit_tribo');
    }

}
