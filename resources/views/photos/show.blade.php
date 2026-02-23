@extends('layouts.app')

@section('title', 'Photo #' . $photo->id . ' - Audit Evidence Map')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 300px; width: 100%; border-radius: 8px; }
    .metadata-table { width: 100%; }
    .metadata-table td { padding: 8px; border-bottom: 1px solid #eee; }
    .metadata-table td:first-child { font-weight: 500; width: 40%; color: #666; }
    .image-container { text-align: center; background: #f5f5f5; padding: 20px; border-radius: 8px; }
    .image-container img { max-width: 100%; max-height: 600px; border-radius: 8px; }
</style>
@endsection

@section('content')
<div style="display: flex; gap: 10px; margin-bottom: 20px;">
    <a href="{{ route('projects.show', $project) }}" class="btn btn-primary">← Back to Project</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <!-- Left: Image -->
    <div>
        <div class="image-container">
            <img src="{{ route('photos.preview', [$project->id, $photo->id]) }}" alt="Photo {{ $photo->id }}">
        </div>
        
        @can('update', $photo)
        <div class="card" style="margin-top: 20px;">
            <h3>Edit Caption</h3>
            <form method="POST" action="{{ route('photos.update', [$project->id, $photo->id]) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <textarea name="caption" rows="3" placeholder="Enter caption...">{{ old('caption', $photo->caption) }}</textarea>
                </div>
                <button type="submit" class="btn btn-success">Save Caption</button>
            </form>
        </div>
        @endcan
    </div>

    <!-- Right: Metadata -->
    <div>
        <div class="card">
            <h3>Photo Metadata</h3>
            <table class="metadata-table">
                <tr>
                    <td>ID</td>
                    <td>#{{ $photo->id }}</td>
                </tr>
                <tr>
                    <td>Uploaded By</td>
                    <td>{{ $photo->uploader->name }}</td>
                </tr>
                <tr>
                    <td>Uploaded At</td>
                    <td>{{ $photo->uploaded_at?->format('Y-m-d H:i:s') }}</td>
                </tr>
                <tr>
                    <td>Taken At</td>
                    <td>
                        {{ $photo->taken_at?->format('Y-m-d H:i:s') ?? 'Unknown' }}
                        <span class="badge badge-{{ $photo->taken_at_source === 'manual' ? 'review' : 'draft' }}">{{ $photo->taken_at_source }}</span>
                        @can('setManualTakenAt', $photo)
                        <button onclick="toggleManualDate()" class="btn btn-primary" style="padding: 2px 10px; font-size: 11px;">Edit</button>
                        @endcan
                    </td>
                </tr>
                <tr>
                    <td>GPS Coordinates</td>
                    <td>
                        @if($photo->hasGps())
                            {{ number_format($photo->exif_lat, 6) }}, {{ number_format($photo->exif_lng, 6) }}
                            <span class="badge badge-{{ $photo->gps_status === 'manual' ? 'review' : 'draft' }}">{{ $photo->gps_status }}</span>
                        @else
                            <span style="color: #e74c3c;">Not available</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>SHA256 Hash</td>
                    <td style="font-size: 11px; word-break: break-all;">{{ $photo->sha256_hash }}</td>
                </tr>
                <tr>
                    <td>Tags</td>
                    <td>{{ $photo->tags ? implode(', ', $photo->tags) : 'None' }}</td>
                </tr>
            </table>
        </div>

        <!-- Manual Date Edit -->
        @can('setManualTakenAt', $photo)
        <div id="manual-date-form" class="card" style="display: none; margin-top: 20px;">
            <h4>Set Manual Date</h4>
            <form method="POST" action="{{ route('photos.update', [$project->id, $photo->id]) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Taken At</label>
                    <input type="datetime-local" name="taken_at" value="{{ $photo->taken_at?->format('Y-m-d\TH:i') }}">
                </div>
                <button type="submit" class="btn btn-success">Save Date</button>
                <button type="button" onclick="toggleManualDate()" class="btn btn-primary">Cancel</button>
            </form>
        </div>
        @endcan

        <!-- Manual Location Map -->
        @can('setManualLocation', $photo)
        <div class="card" style="margin-top: 20px;">
            <h4>Set Manual Location</h4>
            <p style="font-size: 12px; color: #666;">Click on the map to set the photo location.</p>
            <div id="map"></div>
            <form id="location-form" method="POST" action="{{ route('photos.update', [$project->id, $photo->id]) }}" style="margin-top: 15px;">
                @csrf
                @method('PUT')
                <input type="hidden" name="exif_lat" id="lat-input">
                <input type="hidden" name="exif_lng" id="lng-input">
                <div id="location-display" style="margin-bottom: 10px; font-size: 12px;"></div>
                <button type="submit" class="btn btn-success" id="save-location-btn" disabled>Save Location</button>
            </form>
        </div>
        @endcan

        <!-- Download Original -->
        <div class="card" style="margin-top: 20px;">
            <h4>Download</h4>
            <a href="{{ route('photos.original', [$project->id, $photo->id]) }}" class="btn btn-primary" style="width: 100%;">Download Original</a>
        </div>

        @can('delete', $photo)
        <div class="card" style="margin-top: 20px;">
            <h4>Danger Zone</h4>
            <form method="POST" action="{{ route('photos.destroy', [$project->id, $photo->id]) }}" onsubmit="return confirm('Are you sure you want to delete this photo? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" style="width: 100%;">Delete Photo</button>
            </form>
        </div>
        @endcan
    </div>
</div>

<!-- Audit Log for this photo -->
<div class="card" style="margin-top: 20px;">
    <h3>Photo Audit Log</h3>
    @if($photo->auditLogs->count() > 0)
    <table class="metadata-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>User</th>
                <th>Field</th>
                <th>Changes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($photo->auditLogs as $log)
            <tr>
                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->actor->name }}</td>
                <td>{{ $log->field ?? '-' }}</td>
                <td>
                    @if($log->old_value || $log->new_value)
                        {{ json_encode($log->old_value) }} → {{ json_encode($log->new_value) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #666;">No audit log entries for this photo.</p>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, marker;

function initMap() {
    const hasGps = {{ $photo->hasGps() ? 'true' : 'false' }};
    const lat = {{ $photo->exif_lat ?? 'null' }};
    const lng = {{ $photo->exif_lng ?? 'null' }};
    
    const center = hasGps ? [lat, lng] : [-6.2088, 106.8456]; // Default to Jakarta
    const zoom = hasGps ? 15 : 5;
    
    map = L.map('map').setView(center, zoom);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    if (hasGps) {
        marker = L.marker([lat, lng]).addTo(map);
    }
    
    @can('setManualLocation', $photo)
    map.on('click', function(e) {
        const clickedLat = e.latlng.lat;
        const clickedLng = e.latlng.lng;
        
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
        
        document.getElementById('lat-input').value = clickedLat;
        document.getElementById('lng-input').value = clickedLng;
        document.getElementById('location-display').innerHTML = 
            `<strong>Selected:</strong> ${clickedLat.toFixed(6)}, ${clickedLng.toFixed(6)}`;
        document.getElementById('save-location-btn').disabled = false;
    });
    @endcan
}

function toggleManualDate() {
    const form = document.getElementById('manual-date-form');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', initMap);
</script>
@endsection
