<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Gate::authorize('manageMembers', $project);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role_in_project' => ['required', Rule::in(['auditor', 'reviewer', 'readonly'])],
        ]);

        // Check if user is already a member
        if ($project->hasMember(User::find($validated['user_id']))) {
            return back()->with('error', 'User is already a member of this project.');
        }

        $project->members()->attach($validated['user_id'], [
            'role_in_project' => $validated['role_in_project'],
        ]);

        AuditLogService::log($project, 'member_added', 'members', null, [
            'user_id' => $validated['user_id'],
            'role' => $validated['role_in_project'],
        ]);

        return back()->with('success', 'Member added successfully.');
    }

    public function destroy(Project $project, User $user)
    {
        Gate::authorize('manageMembers', $project);

        $project->members()->detach($user->id);

        AuditLogService::log($project, 'member_removed', 'members', [
            'user_id' => $user->id,
        ], null);

        return back()->with('success', 'Member removed successfully.');
    }
}
