<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->hasMember($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAuditor();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($project->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($project->created_by === $user->id) {
            return true;
        }

        return false;
    }

    public function changeStatus(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $memberRole = $project->getMemberRole($user);
        if ($memberRole === 'reviewer') {
            return true;
        }

        return false;
    }
}
