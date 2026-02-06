<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackify - IP Geolocation Map</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }
        
        .container {
            display: flex;
            height: calc(100vh - 100px);
        }
        
        .sidebar {
            width: 350px;
            background: #2a2a2a;
            padding: 20px;
            overflow-y: auto;
            border-right: 1px solid #3a3a3a;
        }
        
        .stats {
            background: #333;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #444;
        }
        
        .stat-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 14px;
        }
        
        .stat-value {
            color: #fff;
            font-weight: bold;
            font-size: 16px;
        }
        
        .captures-list {
            margin-top: 20px;
        }
        
        .capture-item {
            background: #333;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid #667eea;
        }
        
        .capture-item:hover {
            background: #3a3a3a;
            transform: translateX(5px);
        }
        
        .capture-item.active {
            background: #4a4a4a;
            border-left-color: #764ba2;
        }
        
        .capture-ip {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .capture-location {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .capture-time {
            color: #666;
            font-size: 12px;
        }
        
        .map-container {
            flex: 1;
            position: relative;
        }
        
        #map {
            width: 100%;
            height: 100%;
        }
        
        .controls {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #764ba2;
        }
        
        .btn:last-child {
            margin-right: 0;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #aaa;
        }
        
        .error {
            background: #d32f2f;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🗺️ Trackify IP Geolocation Map</h1>
        <p>View all captured IP addresses on an interactive map</p>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <div class="stats" id="stats">
                <div class="stat-item">
                    <span class="stat-label">Total Captures</span>
                    <span class="stat-value" id="total-captures">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Unique Countries</span>
                    <span class="stat-value" id="unique-countries">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Unique IPs</span>
                    <span class="stat-value" id="unique-ips">0</span>
                </div>
            </div>
            
            <div class="captures-list" id="captures-list">
                <div class="loading">Loading captures...</div>
            </div>
        </div>
        
        <div class="map-container">
            <div id="map"></div>
            <div class="controls">
                <button class="btn" onclick="refreshData()">🔄 Refresh</button>
                <button class="btn" onclick="fitBounds()">📍 Fit All</button>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map;
        let markers = [];
        let captures = [];
        
        // Initialize map
        function initMap() {
            map = L.map('map').setView([20, 0], 2);
            
            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
        }
        
        // Load capture data
        async function loadData() {
            try {
                const response = await fetch('api.php');
                const result = await response.json();
                
                if (result.status === 'success') {
                    captures = result.data;
                    displayCaptures();
                    updateStats();
                    plotMarkers();
                } else {
                    showError('Failed to load data');
                }
            } catch (error) {
                console.error('Error loading data:', error);
                showError('Error loading data: ' + error.message);
            }
        }
        
        // Display captures in sidebar
        function displayCaptures() {
            const list = document.getElementById('captures-list');
            
            if (captures.length === 0) {
                list.innerHTML = '<div class="no-data"><div class="no-data-icon">📍</div><p>No captures yet</p></div>';
                return;
            }
            
            list.innerHTML = captures.map((capture, index) => {
                const location = capture.location || `${capture.city}, ${capture.country}`;
                return `
                    <div class="capture-item" onclick="focusMarker(${index})" data-index="${index}">
                        <div class="capture-ip">${capture.ip}</div>
                        <div class="capture-location">📍 ${location}</div>
                        <div class="capture-location">🌐 ${capture.isp}</div>
                        <div class="capture-time">⏰ ${capture.timestamp}</div>
                    </div>
                `;
            }).join('');
        }
        
        // Update statistics
        function updateStats() {
            document.getElementById('total-captures').textContent = captures.length;
            
            const uniqueIPs = new Set(captures.map(c => c.ip));
            document.getElementById('unique-ips').textContent = uniqueIPs.size;
            
            const uniqueCountries = new Set(captures.map(c => c.country).filter(c => c && c !== 'Unknown'));
            document.getElementById('unique-countries').textContent = uniqueCountries.size;
        }
        
        // Plot markers on map
        function plotMarkers() {
            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            captures.forEach((capture, index) => {
                if (capture.latitude && capture.longitude) {
                    const location = capture.location || `${capture.city}, ${capture.country}`;
                    
                    const marker = L.marker([capture.latitude, capture.longitude])
                        .addTo(map)
                        .bindPopup(`
                            <div style="min-width: 200px;">
                                <h3 style="margin: 0 0 10px 0; color: #333;">${capture.ip}</h3>
                                <p style="margin: 5px 0;"><strong>📍 Location:</strong> ${location}</p>
                                <p style="margin: 5px 0;"><strong>🌐 ISP:</strong> ${capture.isp}</p>
                                <p style="margin: 5px 0;"><strong>🏢 Org:</strong> ${capture.org}</p>
                                <p style="margin: 5px 0;"><strong>🕐 Timezone:</strong> ${capture.timezone}</p>
                                <p style="margin: 5px 0;"><strong>⏰ Time:</strong> ${capture.timestamp}</p>
                            </div>
                        `);
                    
                    marker.captureIndex = index;
                    markers.push(marker);
                }
            });
            
            // Fit map to show all markers
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Focus on specific marker
        function focusMarker(index) {
            const capture = captures[index];
            if (capture.latitude && capture.longitude) {
                map.setView([capture.latitude, capture.longitude], 10);
                
                // Highlight in sidebar
                document.querySelectorAll('.capture-item').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelector(`[data-index="${index}"]`).classList.add('active');
                
                // Open popup
                const marker = markers.find(m => m.captureIndex === index);
                if (marker) {
                    marker.openPopup();
                }
            }
        }
        
        // Fit bounds to show all markers
        function fitBounds() {
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Refresh data
        function refreshData() {
            document.getElementById('captures-list').innerHTML = '<div class="loading">Refreshing...</div>';
            loadData();
        }
        
        // Show error message
        function showError(message) {
            document.getElementById('captures-list').innerHTML = `<div class="error">${message}</div>`;
        }
        
        // Initialize on page load
        initMap();
        loadData();
        
        // Auto-refresh every 30 seconds
        setInterval(refreshData, 30000);
    </script>
</body>
</html>
