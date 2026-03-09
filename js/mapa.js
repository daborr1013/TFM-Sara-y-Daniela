const map = L.map('map').setView([52.5200, -1.1743], 5);

function starMarker(color) {
    return L.divIcon({
        className: "",
        html: `
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <filter id="glow-${color.replace('#', '')}">
                        <feGaussianBlur stdDeviation="1.5" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                <!-- Outer mystical rays -->
                <g opacity="0.6" stroke="${color}" stroke-width="0.8" stroke-linecap="round">
                    <line x1="20" y1="2" x2="20" y2="8"/>
                    <line x1="20" y1="32" x2="20" y2="38"/>
                    <line x1="2" y1="20" x2="8" y2="20"/>
                    <line x1="32" y1="20" x2="38" y2="20"/>
                    <line x1="6" y1="6" x2="10" y2="10"/>
                    <line x1="30" y1="30" x2="34" y2="34"/>
                    <line x1="34" y1="6" x2="30" y2="10"/>
                    <line x1="10" y1="30" x2="6" y2="34"/>
                </g>
                <!-- Inner star with ornate design -->
                <path d="M20 4 L24 14 L35 14 L27 20 L30 30 L20 24 L10 30 L13 20 L5 14 L16 14 Z" 
                      fill="${color}" filter="url(#glow-${color.replace('#', '')})"/>
                <!-- Central celestial circle -->
                <circle cx="20" cy="20" r="3" fill="${color}" opacity="0.8"/>
                <!-- Decorative inner points -->
                <circle cx="20" cy="12" r="1.5" fill="${color}" opacity="0.6"/>
                <circle cx="28" cy="20" r="1.5" fill="${color}" opacity="0.6"/>
                <circle cx="20" cy="28" r="1.5" fill="${color}" opacity="0.6"/>
                <circle cx="12" cy="20" r="1.5" fill="${color}" opacity="0.6"/>
            </svg>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 35],
        popupAnchor: [0, -30]
    });
}

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
    minZoom: 2
}).addTo(map);

const markers = L.markerClusterGroup();

const locations = [
    { lat: 52.5200, lng: -1.1743, title: 'ejemplo', description: 'ejemplo' },
];

const marker = L.marker(
    [52.5200, -1.1743],
    { icon: starMarker("#6a4c93") }
).bindPopup("<strong>Ejemplo</strong><br>Ejemplo");

markers.addLayer(marker);
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
