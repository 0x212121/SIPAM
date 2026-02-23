<?php

namespace App\Policies;

use App\Models\PhotoEvidence;
use App\Models\Project;
use App\Models\User;

class PhotoEvidencePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->hasMember($user);
    }

    public function view(User $user, PhotoEvidence $photoEvidence): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $photoEvidence->project->hasMember($user);
    }

    public function create(User $user, Project $project): bool
    {
        if ($project->isFinal()) {
            return $user->isAdmin();
        }

        if ($user->isAdmin()) {
            return true;
        }

        $memberRole = $project->getMemberRole($user);
        return in_array($memberRole, ['auditor', 'reviewer']);
    }

    public function update(User $user, PhotoEvidence $photoEvidence): bool
    {
        $project = $photoEvidence->project;

        if ($project->isFinal()) {
            return $user->isAdmin();
        }

        if ($user->isAdmin()) {
            return true;
        }

        $memberRole = $project->getMemberRole($user);
        return in_array($memberRole, ['auditor', 'reviewer']);
    }

    public function delete(User $user, PhotoEvidence $photoEvidence): bool
    {
        $project = $photoEvidence->project;

        if ($project->isFinal()) {
            return $user->isAdmin();
        }

        if ($user->isAdmin()) {
            return true;
        }

        $memberRole = $project->getMemberRole($user);
        return in_array($memberRole, ['auditor', 'reviewer']);
    }

    public function setManualLocation(User $user, PhotoEvidence $photoEvidence): bool
    {
        return $this->update($user, $photoEvidence);
    }

    public function setManualTakenAt(User $user, PhotoEvidence $photoEvidence): bool
    {
        return $this->update($user, $photoEvidence);
    }
}
