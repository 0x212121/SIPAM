@extends('layouts.app')

@section('title', 'Edit Project - Audit Evidence Map')

@section('content')
<h1>Edit Project: {{ $project->name }}</h1>

<div class="card" style="max-width: 600px;">
    <form method="POST" action="{{ route('projects.update', $project) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="code">Project Code *</label>
            <input type="text" id="code" name="code" value="{{ old('code', $project->code) }}" required>
            @error('code')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="name">Project Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}" required>
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="auditee_agency">Auditee Agency *</label>
            <input type="text" id="auditee_agency" name="auditee_agency" value="{{ old('auditee_agency', $project->auditee_agency) }}" required>
            @error('auditee_agency')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="period_start">Period Start *</label>
            <input type="date" id="period_start" name="period_start" value="{{ old('period_start', $project->period_start->format('Y-m-d')) }}" required>
            @error('period_start')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="period_end">Period End *</label>
            <input type="date" id="period_end" name="period_end" value="{{ old('period_end', $project->period_end->format('Y-m-d')) }}" required>
            @error('period_end')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        @can('changeStatus', $project)
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="draft" {{ old('status', $project->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="review" {{ old('status', $project->status) === 'review' ? 'selected' : '' }}>Review</option>
                <option value="final" {{ old('status', $project->status) === 'final' ? 'selected' : '' }}>Final</option>
            </select>
            <small style="color: #666;">Setting status to "Final" will lock edits for non-admin users.</small>
        </div>
        @endcan

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">Update Project</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-primary">Cancel</a>
        </div>
    </form>

    @can('delete', $project)
    <hr style="margin: 30px 0;">
    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete Project</button>
    </form>
    @endcan
</div>
@endsection
