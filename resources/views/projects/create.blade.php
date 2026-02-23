@extends('layouts.app')

@section('title', 'New Project - Audit Evidence Map')

@section('content')
<h1>Create New Project</h1>

<div class="card" style="max-width: 600px;">
    <form method="POST" action="{{ route('projects.store') }}">
        @csrf

        <div class="form-group">
            <label for="code">Project Code *</label>
            <input type="text" id="code" name="code" value="{{ old('code') }}" required>
            @error('code')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="name">Project Name *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="auditee_agency">Auditee Agency *</label>
            <input type="text" id="auditee_agency" name="auditee_agency" value="{{ old('auditee_agency') }}" required>
            @error('auditee_agency')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="period_start">Period Start *</label>
            <input type="date" id="period_start" name="period_start" value="{{ old('period_start') }}" required>
            @error('period_start')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div class="form-group">
            <label for="period_end">Period End *</label>
            <input type="date" id="period_end" name="period_end" value="{{ old('period_end') }}" required>
            @error('period_end')<span class="text-danger">{{ $message }}</span>@enderror
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">Create Project</button>
            <a href="{{ route('projects.index') }}" class="btn btn-primary">Cancel</a>
        </div>
    </form>
</div>
@endsection
