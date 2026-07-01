<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitlistEntry;

class WaitlistEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_campista');
    }

    public function view(User $user, WaitlistEntry $waitlistEntry): bool
    {
        return $user->can('view_campista');
    }

    public function create(User $user): bool
    {
        return $user->can('create_campista');
    }

    public function update(User $user, WaitlistEntry $waitlistEntry): bool
    {
        return $user->can('update_campista');
    }

    public function delete(User $user, WaitlistEntry $waitlistEntry): bool
    {
        return $user->can('delete_campista');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_campista');
    }

    public function generateInvitation(User $user, WaitlistEntry $waitlistEntry): bool
    {
        return $user->can('create_campista') && $user->can('update_campista');
    }
}
