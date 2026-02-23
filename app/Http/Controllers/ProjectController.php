<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()
            ->with('creator')
            ->withCount('photoEvidences');

        // Filter by status if provided
        if ($request->has('status') && in_array($request->status, Project::STATUSES)) {
            $query->where('status', $request->status);
        }

        // Non-admins only see projects they are members of
        if (!auth()->user()->isAdmin()) {
            $query->whereHas('members', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        Gate::authorize('create', Project::class);
        return view('projects.create');
    }

    public function store(ProjectRequest $request)
    {
        Gate::authorize('create', Project::class);

        $project = Project::create([
            'code' => $request->code,
            'name' => $request->name,
            'auditee_agency' => $request->auditee_agency,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'status' => Project::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        // Add creator as project member with auditor role
        $project->members()->attach(auth()->id(), ['role_in_project' => 'auditor']);

        AuditLogService::logCreate($project);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['members', 'creator']);
        
        // Get photos count by GPS status
        $photoStats = $project->photoEvidences()
            ->selectRaw('gps_status, count(*) as count')
            ->groupBy('gps_status')
            ->pluck('count', 'gps_status');

        return view('projects.show', compact('project', 'photoStats'));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);
        return view('projects.edit', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $oldStatus = $project->status;
        $oldValues = $project->only(['code', 'name', 'auditee_agency', 'period_start', 'period_end', 'status']);

        $project->update([
            'code' => $request->code,
            'name' => $request->name,
            'auditee_agency' => $request->auditee_agency,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
        ]);

        // Handle status change separately (requires different permission)
        if ($request->has('status') && $request->status !== $oldStatus) {
            Gate::authorize('changeStatus', $project);
            $project->update(['status' => $request->status]);
            AuditLogService::logStatusChange($project, $oldStatus, $request->status);
        }

        // Log other changes
        foreach ($project->getChanges() as $field => $newValue) {
            if ($field !== 'updated_at' && isset($oldValues[$field])) {
                AuditLogService::logUpdate($project, $field, $oldValues[$field], $newValue);
            }
        }

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        AuditLogService::logDelete($project);
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
