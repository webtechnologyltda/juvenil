<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Campista;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampistaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_campista');
    }

    public function view(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('view_campista');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_campista');
    }

    public function update(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('update_campista');
    }

    public function delete(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('delete_campista');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_campista');
    }

    public function restore(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('restore_campista');
    }

    public function forceDelete(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('force_delete_campista');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_campista');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_campista');
    }

    public function replicate(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('replicate_campista');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_campista');
    }

    public function updateTribo(AuthUser $authUser, Campista $campista): bool
    {
        return $authUser->can('updateTribo_campista');
    }

    public function audit(AuthUser $authUser): bool
    {
        return $authUser->can('audit_campista');
    }

    public function restoreAudit(AuthUser $authUser): bool
    {
        return $authUser->can('restoreAudit_campista');
    }

    public function export(AuthUser $authUser): bool
    {
        return $authUser->can('export_campista');
    }

}
