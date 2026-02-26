@extends('layouts.app')

@section('title', $project->name . ' - Audit Evidence Map')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<style>
    #map { height: 500px; width: 100%; }
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
    .gallery-item { position: relative; cursor: pointer; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .gallery-item img { width: 100%; height: 150px; object-fit: cover; }
    .gallery-item .caption { padding: 10px; font-size: 12px; background: white; }
    .photo-upload-zone { border: 2px dashed #ddd; border-radius: 8px; padding: 40px; text-align: center; cursor: pointer; }
    .photo-upload-zone:hover { border-color: #3498db; background: #f8f9fa; }
</style>
@endsection

@section('content')
<div class="d-flex justify-between align-center" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>{{ $project->name }}</h1>
        <p style="color: #666;">{{ $project->code }} | {{ $project->auditee_agency }}</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <span class="badge badge-{{ $project->status }}">{{ ucfirst($project->status) }}</span>
        @can('update', $project)
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Edit</a>
        @endcan
    </div>
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
    <div class="card" style="padding: 20px; text-align: center;">
        <h3>{{ $project->photo_evidences_count }}</h3>
        <p style="color: #666; margin: 0;">Total Photos</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3>{{ $photoStats['original'] ?? 0 }}</h3>
        <p style="color: #666; margin: 0;">With GPS (Original)</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3>{{ $photoStats['manual'] ?? 0 }}</h3>
        <p style="color: #666; margin: 0;">With GPS (Manual)</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3>{{ $photoStats['missing'] ?? 0 }}</h3>
        <p style="color: #666; margin: 0;">Missing GPS</p>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active" onclick="showTab('map')">Map</div>
    <div class="tab" onclick="showTab('gallery')">Gallery</div>
    <div class="tab" onclick="showTab('upload')">Upload Photos</div>
    <div class="tab" onclick="showTab('members')">Members</div>
    <div class="tab" onclick="showTab('audit')">Audit Log</div>
</div>

<!-- Map Tab -->
<div id="tab-map" class="tab-content active">
    <div class="card">
        <div style="margin-bottom: 15px; display: flex; gap: 15px; align-items: center;">
            <label>
                <input type="checkbox" id="only-with-gps" checked onchange="loadMap()">
                Only show photos with GPS
            </label>
        </div>
        <div id="map"></div>
    </div>
</div>

<!-- Gallery Tab -->
<div id="tab-gallery" class="tab-content">
    <div class="card">
        <div id="gallery-content">
            <p>Loading gallery...</p>
        </div>
    </div>
</div>

<!-- Upload Tab -->
<div id="tab-upload" class="tab-content">
    <div class="card">
        @can('create', [App\Models\PhotoEvidence::class, $project])
        <form method="POST" action="{{ route('photos.store', $project) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Select Photos (multiple allowed, max 50)</label>
                <input type="file" name="photos[]" multiple accept="image/*" required>
                <small style="color: #666;">Supported formats: JPEG, PNG, GIF, WebP. Max 50MB per file.</small>
            </div>
            <button type="submit" class="btn btn-success">Upload Photos</button>
        </form>
        @else
        <p>You don't have permission to upload photos to this project.</p>
        @endcan
    </div>
</div>

<!-- Members Tab -->
<div id="tab-members" class="tab-content">
    <div class="card">
        <h3>Project Members</h3>
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    @can('manageMembers', $project)
                    <th>Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach($project->members as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ ucfirst($member->pivot->role_in_project) }}</td>
                    @can('manageMembers', $project)
                    <td>
                        @if($member->id !== auth()->id())
                        <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}" style="display: inline;" onsubmit="return confirm('Remove this member?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">Remove</button>
                        </form>
                        @endif
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>

        @can('manageMembers', $project)
        <hr style="margin: 30px 0;">
        <h4>Add Member</h4>
        <form method="POST" action="{{ route('projects.members.store', $project) }}" style="display: flex; gap: 15px; margin-top: 15px;">
            @csrf
            <select name="user_id" required style="flex: 1;">
                <option value="">Select User...</option>
                @foreach(App\Models\User::whereNotIn('id', $project->members->pluck('id'))->get() as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            <select name="role_in_project" required>
                <option value="auditor">Auditor</option>
                <option value="reviewer">Reviewer</option>
                <option value="readonly">Readonly</option>
            </select>
            <button type="submit" class="btn btn-success">Add</button>
        </form>
        @endcan
    </div>
</div>

<!-- Audit Log Tab -->
<div id="tab-audit" class="tab-content">
    <div class="card">
        <h3>Project Audit Log</h3>
        <div id="audit-log-content">
            <p>Loading audit log...</p>
        </div>
    </div>
</div>

<!-- Export buttons -->
<div class="card" style="margin-top: 20px;">
    <h4>Export Data</h4>
    <div style="display: flex; gap: 10px; margin-top: 10px;">
        <a href="{{ route('projects.export.csv', $project) }}" class="btn btn-primary">Download CSV</a>
        <a href="{{ route('projects.export.geojson', $project) }}" class="btn btn-primary">Download GeoJSON</a>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
let map, markers;
let photosData = [];

function showTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    
    event.target.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
    
    if (tabName === 'map') {
        setTimeout(() => map && map.invalidateSize(), 100);
    } else if (tabName === 'gallery') {
        loadGallery();
    } else if (tabName === 'audit') {
        loadAuditLog();
    }
}

function initMap() {
    map = L.map('map').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    markers = L.markerClusterGroup();
    map.addLayer(markers);
    
    loadPhotos();
}

function loadPhotos() {
    fetch('{{ url('/api/projects/' . $project->id . '/photos') }}', {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status + ': ' + r.statusText);
            }
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return r.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Expected JSON but got ' + (contentType || 'unknown'));
                });
            }
            return r.json();
        })
        .then(data => {
            photosData = data;
            loadMap();
        })
        .catch(err => {
            console.error('Failed to load photos:', err);
            document.getElementById('map').innerHTML = '<p style="padding: 20px; color: #e74c3c;">Error loading photos. Please refresh the page.</p>';
        });
}

function loadMap() {
    markers.clearLayers();
    
    const onlyWithGps = document.getElementById('only-with-gps').checked;
    let bounds = [];
    
    photosData.forEach(photo => {
        if (!photo.exif_lat || !photo.exif_lng) return;
        
        bounds.push([photo.exif_lat, photo.exif_lng]);
        
        const marker = L.marker([photo.exif_lat, photo.exif_lng]);
        const popup = `
            <div style="text-align: center;">
                <img src="${photo.thumb_url}" style="max-width: 150px; margin-bottom: 5px;"><br>
                <strong>${photo.caption || 'No caption'}</strong><br>
                <small>${photo.taken_at || 'No date'}</small><br>
                <a href="${photo.url}">View Details</a>
            </div>
        `;
        marker.bindPopup(popup);
        markers.addLayer(marker);
    });
    
    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}

function loadGallery() {
    const container = document.getElementById('gallery-content');
    if (!photosData.length) {
        container.innerHTML = '<p>No photos yet.</p>';
        return;
    }
    
    let html = '<div class="gallery-grid">';
    photosData.forEach(photo => {
        html += `
            <a href="${photo.url}" class="gallery-item" style="text-decoration: none; color: inherit;">
                <img src="${photo.preview_url || photo.thumb_url}" loading="lazy">
                <div class="caption">
                    ${photo.caption || 'No caption'}<br>
                    <small style="color: #666;">${photo.taken_at || 'No date'}</small>
                    ${photo.gps_status !== 'missing' ? '<span style="color: #27ae60;">✓ GPS</span>' : '<span style="color: #e74c3c;">✗ No GPS</span>'}
                </div>
            </a>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function loadAuditLog() {
    const container = document.getElementById('audit-log-content');
    fetch('{{ url('/api/projects/' . $project->id . '/audit-logs') }}', {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(logs => {
            if (!logs.length) {
                container.innerHTML = '<p>No audit log entries.</p>';
                return;
            }
            
            let html = '<table><thead><tr><th>Time</th><th>Action</th><th>User</th><th>Details</th></tr></thead><tbody>';
            logs.forEach(log => {
                html += `<tr>
                    <td>${new Date(log.created_at).toLocaleString()}</td>
                    <td>${log.action}</td>
                    <td>${log.actor?.name || 'System'}</td>
                    <td>${log.field || '-'} ${log.old_value ? `(${JSON.stringify(log.old_value)} → ${JSON.stringify(log.new_value)})` : ''}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        })
        .catch(err => {
            console.error('Failed to load audit logs:', err);
            container.innerHTML = '<p style="color: #e74c3c;">Error loading audit logs.</p>';
        });
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', initMap);
</script>
@endsection
