<?php

namespace App\Policies;

use App\Models\Finding;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['finding.view-all', 'finding.view-assigned']);
    }

    public function view(User $user, Finding $finding): bool
    {
        if ($user->hasPermissionTo('finding.view-all')) {
            return true;
        }

        if ($user->isAuditor()) {
            return $finding->audit->auditor_id === $user->id || $finding->audit->auditors()->where('users.id', $user->id)->exists();
        }

        if ($user->isAuditee()) {
            return $user->stores()->where('stores.id', $finding->audit->store_id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finding.create');
    }

    public function update(User $user, Finding $finding): bool
    {
        if ($user->hasPermissionTo('finding.edit') && $user->isAuditor()) {
            return $finding->audit->auditor_id === $user->id || $finding->audit->auditors()->where('users.id', $user->id)->exists();
        }

        // Admin and Coordinator can edit
        return $user->hasPermissionTo('finding.view-all');
    }

    public function delete(User $user, Finding $finding): bool
    {
        if ($user->hasPermissionTo('finding.delete')) {
            if ($user->isAuditor()) {
                return $finding->audit->auditor_id === $user->id || $finding->audit->auditors()->where('users.id', $user->id)->exists();
            }
            return true;
        }

        return false;
    }

    public function verify(User $user, Finding $finding): bool
    {
        if (! $user->hasPermissionTo('finding.verify')) {
            return false;
        }

        return $finding->audit->auditor_id === $user->id || $finding->audit->auditors()->where('users.id', $user->id)->exists();
    }

    public function close(User $user, Finding $finding): bool
    {
        if ($user->isAuditor()) {
            return false;
        }

        if (! $user->hasPermissionTo('finding.close')) {
            return false;
        }

        return $finding->isCloseable();
    }
}
