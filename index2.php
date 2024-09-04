<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaflet with OpenStreetMap</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevent scrollbars */
        }
        #map {
            height: 100vh; /* Full viewport height */
            width: 100vw;  /* Full viewport width */
        }
        .controls {
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.9); /* Slightly transparent background */
            border: 1px solid #ddd;
            border-radius: 10px;
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }
        .controls h2 {
            margin-top: 0;
            font-size: 18px;
            color: #333;
        }
        .controls label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }
        .controls input {
            width: calc(100% - 20px); /* Full width with padding */
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
        }
        .controls input:read-only {
            background-color: #f9f9f9;
        }
        .controls button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .controls button:hover {
            background-color: #0056b3;
        }
    </style>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <div class="controls">
        <h2>Pin Location</h2>
        <label for="lat">Latitude:</label>
        <input type="number" id="lat" step="any" readonly>
        <label for="lng">Longitude:</label>
        <input type="number" id="lng" step="any" readonly>
        <button id="locateButton">Use My Location</button>
        <button id="saveButton">Save Location</button>
        <button id="loadButton">Load Saved Locations</button>
    </div>
    <div id="map"></div>

    <script>
        // Initialize the map
        const map = L.map('map').setView([14.5995, 120.9842], 12); // Center on Manila with zoom level 12

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Create a default icon
        const defaultIcon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
            shadowSize: [41, 41]
        });

        // Create a custom FontAwesome icon
        const redIcon = L.divIcon({
            html: '<i class="fa-solid fa-map-pin" style="color: #ff2600; font-size: 25px;"></i>',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });

        // Create a draggable marker with initial position and default icon
        let marker = L.marker([14.5995, 120.9842], {
            draggable: true,
            icon: defaultIcon
        }).addTo(map)
            .bindPopup('Drag me to a new location')
            .openPopup();

        // Update input fields with marker coordinates
        function updateCoordinates(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(4);
            document.getElementById('lng').value = lng.toFixed(4);
        }

        // Initialize input fields with marker's initial position
        updateCoordinates(marker.getLatLng().lat, marker.getLatLng().lng);

        // Event listener for marker move event
        marker.on('move', function(event) {
            const latlng = event.latlng;
            updateCoordinates(latlng.lat, latlng.lng);
        });

        // Event listener for marker dragend event
        marker.on('dragend', function(event) {
            const latlng = event.latlng;
            marker.setPopupContent(`Location: ${latlng.lat.toFixed(4)}, ${latlng.lng.toFixed(4)}`);
        });

        // Function to handle geolocation
        function locateUser() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Move the map view to user's location
                    map.setView([lat, lng], 12);

                    // Optionally, move the marker to the user's location
                    marker.setLatLng([lat, lng]);

                    // Update input fields with new coordinates
                    updateCoordinates(lat, lng);

                    // Change marker icon to red with FontAwesome pin
                    marker.setIcon(redIcon);
                    
                    // Set popup content for the marker
                    marker.setPopupContent(`You are here: ${lat.toFixed(4)}, ${lng.toFixed(4)}`).openPopup();
                }, function(error) {
                    console.error('Error getting location:', error);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        // Function to save the marker location to the database
        function saveLocation() {
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;

            if (lat && lng) {
                $.ajax({
                    url: 'save_location.php',
                    type: 'POST',
                    data: { latitude: lat, longitude: lng },
                    success: function(response) {
                        alert(response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving location:', error);
                    }
                });
            } else {
                alert('Please move the marker to the desired location first.');
            }
        }

        // Function to load saved locations from the database
        function loadLocations() {
            $.ajax({
                url: 'get_locations.php',
                type: 'GET',
                dataType: 'json',
                success: function(locations) {
                    locations.forEach(location => {
                        L.marker([location.latitude, location.longitude]).addTo(map)
                            .bindPopup(`Saved Location: ${location.latitude}, ${location.longitude}`);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error loading locations:', error);
                }
            });
        }

        // Event listeners for buttons
        document.getElementById('locateButton').addEventListener('click', locateUser);
        document.getElementById('saveButton').addEventListener('click', saveLocation);
        document.getElementById('loadButton').addEventListener('click', loadLocations);
    </script>
</body>
</html>
