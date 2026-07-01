<?php
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn { padding: 8px 16px; background: #0066cc; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;}
    .btn-outline { background: transparent; border: 1px solid #0066cc; color: #0066cc; }
    .btn-small { padding: 4px 8px; font-size: 11px; }
    
    .area-card { background: #fff; border: 1px solid var(--mac-border); border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
    @media (prefers-color-scheme: dark) { .area-card { background: rgba(0,0,0,0.2); } }
    .area-header { padding: 15px 20px; background: rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; cursor: pointer;}
    .area-header:hover { background: rgba(0,0,0,0.05); }
    .area-title { font-size: 16px; font-weight: bold; margin:0; display: flex; align-items: center; gap: 10px;}
    .area-coords { font-size: 12px; color: #666; font-weight: normal; }
    
    .mca-table { width: 100%; border-collapse: collapse; }
    .mca-table th, .mca-table td { padding: 10px 20px; text-align: left; border-top: 1px solid var(--mac-border); font-size: 13px;}
    .mca-table th { background: rgba(0,102,204,0.05); color: #0066cc; font-weight: 600;}
    
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: var(--mac-bg); padding: 25px; border-radius: 12px; width: 1000px; border: 1px solid var(--mac-border); display: flex; gap: 20px;}
    .map-container { width: 100%; height: 550px; border-radius: 8px; background: #eee; border: 1px solid #ccc;}
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--mac-border); border-radius: 4px; background: transparent; color: var(--text-main); box-sizing: border-box;}
    
    .search-box { display: flex; gap: 5px; margin-bottom: 5px; }
    .search-btn { padding: 8px 12px; background: #333; color: #fff; border: none; border-radius: 4px; cursor: pointer;}
    .variance-badge { padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
</style>

<div class="card">
    <div class="header-actions">
        <h2>Territory & Routing System</h2>
        <button class="btn" onclick="openMapModal('main')">+ Create Main Area</button>
    </div>

    <?php if(!empty($data['error'])): ?>
        <div style="padding: 10px; background:#ffebee; color:#c62828; border-radius:4px; margin-bottom:15px;"><?= $data['error'] ?></div>
    <?php endif; ?>
    <?php if(!empty($data['success'])): ?>
        <div style="padding: 10px; background:#e8f5e9; color:#2e7d32; border-radius:4px; margin-bottom:15px;"><?= $data['success'] ?></div>
    <?php endif; ?>

    <?php if(empty($data['main_areas'])): ?>
        <p style="text-align: center; color: #888; padding: 40px;">No territories mapped yet. Create a Main Area to begin.</p>
    <?php else: ?>
        <?php foreach($data['main_areas'] as $area): ?>
            <div class="area-card">
                <div class="area-header" onclick="toggleMca('mca_<?= $area->id ?>')">
                    <h3 class="area-title">
                        📍 <?= htmlspecialchars($area->name) ?> 
                        <span class="area-coords">(Lat: <?= round($area->latitude, 4) ?>, Lng: <?= round($area->longitude, 4) ?>)</span>
                    </h3>
                    <button class="btn btn-outline btn-small" onclick="event.stopPropagation(); openMapModal('mca', <?= $area->id ?>, '<?= htmlspecialchars(addslashes($area->name)) ?>')">+ Add MCA Route</button>
                </div>
                
                <div id="mca_<?= $area->id ?>" style="display: none;">
                    <table class="mca-table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Master Card Area (Route)</th>
                                <th style="width: 25%;">GPS Waypoints</th>
                                <th style="width: 15%;">Budget (KM)</th>
                                <th style="width: 20%;">Actual Path (KM)</th>
                                <th style="width: 15%;">Dist. from Main</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($area->mcas)): ?>
                                <tr><td colspan="5" style="text-align: center; color: #888; padding: 15px;">No MCA routes mapped to this area yet.</td></tr>
                            <?php else: foreach($area->mcas as $mca): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($mca->name) ?></strong></td>
                                    <td style="font-size: 11px;">
                                        <span style="color:#2e7d32;">● <?= round($mca->start_lat, 4) ?>, <?= round($mca->start_lng, 4) ?></span> <br>
                                        <span style="color:#c62828;">● <?= round($mca->end_lat, 4) ?>, <?= round($mca->end_lng, 4) ?></span>
                                    </td>
                                    <td style="font-weight: bold;"><?= number_format($mca->budget_km, 1) ?> KM</td>
                                    
                                    <?php 
                                        $variance = $mca->actual_route_km - $mca->budget_km; 
                                        $isOver = $variance > 0;
                                    ?>
                                    <td>
                                        <span style="color: #0066cc; font-weight: bold; font-size: 14px;"><?= number_format($mca->actual_route_km, 1) ?> KM</span><br>
                                        <?php if($mca->budget_km > 0): ?>
                                            <span class="variance-badge" style="background: <?= $isOver ? '#ffebee' : '#e8f5e9' ?>; color: <?= $isOver ? '#c62828' : '#2e7d32' ?>;">
                                                <?= $isOver ? '+' : '' ?><?= number_format($variance, 1) ?> KM
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #888;"><?= number_format($mca->distance_to_start_km ?? 0, 1) ?> KM</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal" id="mapModal">
    <div class="modal-content">
        <!-- Form Side -->
        <div style="flex: 1; padding-right: 15px; border-right: 1px solid var(--mac-border); max-width: 350px; overflow-y: auto;">
            <h3 id="modalTitle" style="margin-top:0; color:#0066cc;">Create Territory</h3>
            
            <form action="<?= APP_URL ?>/territory" method="POST" id="territoryForm">
                <input type="hidden" name="action" id="formAction" value="add_main_area">
                <input type="hidden" name="main_area_id" id="formParentId" value="">
                
                <div class="form-group">
                    <label id="nameLabel">Route/Area Name *</label>
                    <input type="text" name="name" id="formName" class="form-control" placeholder="e.g. From Dambokka to Rambukkana" required>
                </div>

                <!-- Single Point Inputs (Main Area) -->
                <div id="singlePointInputs">
                    <div class="search-box">
                        <input type="text" id="mainSearchInput" class="form-control" placeholder="Search City...">
                        <button type="button" class="search-btn" onclick="searchLocation('main')">Search</button>
                    </div>
                    <div class="form-group" style="display:none;"><input type="text" name="latitude" id="formLat" class="form-control"><input type="text" name="longitude" id="formLng" class="form-control"></div>
                </div>

                <!-- Route Inputs (MCA) -->
                <div id="routePointInputs" style="display: none;">
                    <div class="form-group" style="background: rgba(46, 125, 50, 0.05); padding: 10px; border-radius: 4px; border: 1px solid rgba(46, 125, 50, 0.2);">
                        <label style="color:#2e7d32;">Start Location</label>
                        <div class="search-box">
                            <input type="text" id="startSearchInput" class="form-control" placeholder="e.g. Dambokka">
                            <button type="button" class="search-btn" onclick="searchLocation('start')">Search</button>
                        </div>
                        <input type="hidden" name="start_lat" id="formStartLat" required>
                        <input type="hidden" name="start_lng" id="formStartLng" required>
                    </div>

                    <div class="form-group" style="background: rgba(198, 40, 40, 0.05); padding: 10px; border-radius: 4px; border: 1px solid rgba(198, 40, 40, 0.2);">
                        <label style="color:#c62828;">End Location</label>
                        <div class="search-box">
                            <input type="text" id="endSearchInput" class="form-control" placeholder="e.g. Rambukkana">
                            <button type="button" class="search-btn" onclick="searchLocation('end')">Search</button>
                        </div>
                        <input type="hidden" name="end_lat" id="formEndLat" required>
                        <input type="hidden" name="end_lng" id="formEndLng" required>
                    </div>

                    <div style="background: #f4f5f7; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #ddd;">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Budget Route Distance (KM) *</label>
                            <input type="number" name="budget_km" id="budgetKm" step="0.1" min="0" class="form-control" value="0.0" required style="border-color:#0066cc;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Real Driving Distance (Map)</label>
                            <div style="font-size: 20px; font-weight: bold; color: #0066cc;" id="actualKmDisplay">0.0 KM</div>
                            <input type="hidden" name="actual_route_km" id="formActualKm" value="0">
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline btn-small" onclick="resetRouteMap()">Clear Map</button>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeMapModal()">Cancel</button>
                    <button type="submit" class="btn">Save Mapping</button>
                </div>
            </form>
        </div>
        
        <!-- Map Side -->
        <div style="flex: 2;">
            <div id="interactiveMap" class="map-container"></div>
        </div>
    </div>
</div>

<script>
    let map = null;
    let mainMarker = null;
    let startMarker = null;
    let endMarker = null;
    let routeLine = null;
    let currentMode = 'main';

    const greenIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41]
    });
    const redIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41], iconAnchor: [12, 41]
    });

    function toggleMca(id) {
        const el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }

    function initMap() {
        if(map !== null) return;
        map = L.map('interactiveMap').setView([7.8731, 80.7718], 7); 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

        map.on('click', function(e) {
            if (currentMode === 'main') {
                placeMainMarker(e.latlng.lat, e.latlng.lng);
            } else if (currentMode === 'mca') {
                if (!startMarker) {
                    placeStartMarker(e.latlng.lat, e.latlng.lng);
                } else if (!endMarker) {
                    placeEndMarker(e.latlng.lat, e.latlng.lng);
                }
            }
        });
    }

    function placeMainMarker(lat, lng) {
        if(mainMarker) map.removeLayer(mainMarker);
        mainMarker = L.marker([lat, lng]).addTo(map);
        document.getElementById('formLat').value = lat;
        document.getElementById('formLng').value = lng;
    }

    function placeStartMarker(lat, lng) {
        if(startMarker) map.removeLayer(startMarker);
        startMarker = L.marker([lat, lng], {icon: greenIcon}).addTo(map);
        document.getElementById('formStartLat').value = lat;
        document.getElementById('formStartLng').value = lng;
        drawRealRoute();
    }

    function placeEndMarker(lat, lng) {
        if(endMarker) map.removeLayer(endMarker);
        endMarker = L.marker([lat, lng], {icon: redIcon}).addTo(map);
        document.getElementById('formEndLat').value = lat;
        document.getElementById('formEndLng').value = lng;
        drawRealRoute();
    }

    // NEW: Open Source Routing Machine (OSRM) integration for real driving paths
    async function drawRealRoute() {
        if (routeLine) map.removeLayer(routeLine);
        
        if (startMarker && endMarker) {
            const start = startMarker.getLatLng();
            const end = endMarker.getLatLng();

            // OSRM API expects Longitude,Latitude
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${start.lng},${start.lat};${end.lng},${end.lat}?overview=full&geometries=geojson`;

            try {
                const response = await fetch(osrmUrl);
                const data = await response.json();

                if(data.routes && data.routes.length > 0) {
                    const route = data.routes[0];
                    const distanceKm = (route.distance / 1000).toFixed(2); // Convert meters to KM

                    // Update Hidden Input & UI
                    document.getElementById('formActualKm').value = distanceKm;
                    document.getElementById('actualKmDisplay').innerText = distanceKm + ' KM';

                    // Draw the actual roads on the map using GeoJSON
                    routeLine = L.geoJSON(route.geometry, {
                        style: { color: '#0066cc', weight: 5, opacity: 0.8 }
                    }).addTo(map);

                    map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});
                } else {
                    fallbackToStraightLine(start, end);
                }
            } catch(e) {
                console.error("Routing Error:", e);
                fallbackToStraightLine(start, end);
            }
        }
    }

    function fallbackToStraightLine(start, end) {
        routeLine = L.polyline([start, end], {color: '#c62828', weight: 4, dashArray: '5, 10'}).addTo(map);
        map.fitBounds(routeLine.getBounds(), {padding: [50, 50]});
        document.getElementById('actualKmDisplay').innerText = "Routing Failed";
    }

    function resetRouteMap() {
        if(startMarker) map.removeLayer(startMarker);
        if(endMarker) map.removeLayer(endMarker);
        if(routeLine) map.removeLayer(routeLine);
        startMarker = null; endMarker = null; routeLine = null;
        document.getElementById('formStartLat').value = ''; document.getElementById('formStartLng').value = '';
        document.getElementById('formEndLat').value = ''; document.getElementById('formEndLng').value = '';
        document.getElementById('formActualKm').value = '0';
        document.getElementById('actualKmDisplay').innerText = '0.0 KM';
    }

    async function searchLocation(type) {
        let inputId = type === 'main' ? 'mainSearchInput' : (type === 'start' ? 'startSearchInput' : 'endSearchInput');
        const query = document.getElementById(inputId).value;
        if(!query) return;

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Sri Lanka')}&limit=1`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            if(data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                
                map.flyTo([lat, lng], 13);
                
                if (type === 'main') {
                    placeMainMarker(lat, lng);
                    if(!document.getElementById('formName').value) document.getElementById('formName').value = query;
                } else if (type === 'start') {
                    placeStartMarker(lat, lng);
                    updateRouteName();
                } else if (type === 'end') {
                    placeEndMarker(lat, lng);
                    updateRouteName();
                }
            } else {
                alert("Location not found. Try searching a nearby town.");
            }
        } catch (error) { console.error("Geocoding Error: ", error); }
    }

    function updateRouteName() {
        const start = document.getElementById('startSearchInput').value;
        const end = document.getElementById('endSearchInput').value;
        if(start && end) {
            document.getElementById('formName').value = `From ${start} To ${end}`;
        }
    }

    function openMapModal(mode, parentId = '', parentName = '') {
        document.getElementById('mapModal').style.display = 'flex';
        currentMode = mode;
        initMap();
        setTimeout(() => { map.invalidateSize(); }, 200);

        document.getElementById('formName').value = '';
        
        if (mainMarker) map.removeLayer(mainMarker);
        resetRouteMap();
        map.setView([7.8731, 80.7718], 7);

        if(mode === 'main') {
            document.getElementById('modalTitle').innerText = 'Create Main Area';
            document.getElementById('nameLabel').innerText = 'Main Area Name *';
            document.getElementById('formAction').value = 'add_main_area';
            document.getElementById('singlePointInputs').style.display = 'block';
            document.getElementById('routePointInputs').style.display = 'none';
        } else {
            document.getElementById('modalTitle').innerText = 'Add Route to: ' + parentName;
            document.getElementById('nameLabel').innerText = 'Route Name *';
            document.getElementById('formAction').value = 'add_mca';
            document.getElementById('formParentId').value = parentId;
            document.getElementById('singlePointInputs').style.display = 'none';
            document.getElementById('routePointInputs').style.display = 'block';
        }
    }

    function closeMapModal() {
        document.getElementById('mapModal').style.display = 'none';
    }
</script>