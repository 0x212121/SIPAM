@extends('layouts.app')

@section('title', 'Projects - Audit Evidence Map')

@section('content')
<div class="d-flex justify-between align-center" style="display: flex; justify-content: space-between; align-items: center;">
    <h1>Projects</h1>
    @can('create', App\Models\Project::class)
    <a href="{{ route('projects.create') }}" class="btn btn-success">+ New Project</a>
    @endcan
</div>

<!-- Filter -->
<div class="card" style="margin-top: 20px; padding: 15px;">
    <form method="GET" action="{{ route('projects.index') }}" style="display: flex; gap: 15px; align-items: center;">
        <label>Filter by Status:</label>
        <select name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="review" {{ request('status') === 'review' ? 'selected' : '' }}>Review</option>
            <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
        </select>
        @if(request('status'))
            <a href="{{ route('projects.index') }}" class="btn btn-primary" style="padding: 5px 15px;">Clear</a>
        @endif
    </form>
</div>

<!-- Projects Table -->
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Auditee Agency</th>
                <th>Period</th>
                <th>Status</th>
                <th>Photos</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
            <tr>
                <td>{{ $project->code }}</td>
                <td>{{ $project->name }}</td>
                <td>{{ $project->auditee_agency }}</td>
                <td>{{ $project->period_start->format('M Y') }} - {{ $project->period_end->format('M Y') }}</td>
                <td>
                    <span class="badge badge-{{ $project->status }}">{{ ucfirst($project->status) }}</span>
                </td>
                <td>{{ $project->photo_evidences_count }}</td>
                <td>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-primary" style="padding: 5px 15px; font-size: 12px;">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px;">No projects found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        {{ $projects->links() }}
    </div>
</div>
@endsection
