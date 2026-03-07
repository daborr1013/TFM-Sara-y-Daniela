const map = L.map('map').setView([52.5200, -1.1743], 5);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
    minZoom: 2
}).addTo(map);

const markers = L.markerClusterGroup();

const locations = [
    { lat: 52.5200, lng: -1.1743, title: 'ejemplo', description: 'ejemplo' },
];

locations.forEach(location => {
    const marker = L.marker([location.lat, location.lng])
        .bindPopup(`<strong>${location.title}</strong><br>${location.description}`);
    markers.addLayer(marker);
});

map.addLayer(markers);

// Gran Bretaña GeoJSON
Promise.all([
    fetch('/TFM-Sara-y-Daniela/data/paises/gb.geo.json').then(r => r.json())
])
.then(([geojson]) => {
    console.log('GeoJSON loaded:', geojson);
    L.geoJSON(geojson, {
        style: {
            color: '#6a4c93',
            weight: 1,
            fillColor: '#6a4c93',
            fillOpacity: 0.5,
            fill: true
        }
    }).addTo(map);
})
.catch(error => console.error('Error loading GeoJSON:', error));
