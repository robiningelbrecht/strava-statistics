export default function Heatmap($heatmap, routes) {
    const determineMostActiveState = () => {
        const stateCounts = routes.reduce((counts, route) => {
            const state = route.location.state;
            if (state) counts[state] = (counts[state] || 0) + 1;
            return counts;
        }, {});

        return Object.keys(stateCounts).reduce((a, b) => stateCounts[a] > stateCounts[b] ? a : b, '');
    };

    const render = () => {
        const mostActiveState = determineMostActiveState();

        const map = L.map($heatmap, {
            scrollWheelZoom: false,
            minZoom: 1,
            maxZoom: 21,
        });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const places = [];
        const countryFeatureGroups = new Map();
        const mainFeatureGroup = L.featureGroup();
        const fitMapBoundsFeatureGroup = L.featureGroup();

        routes.forEach(route => {
            const {countryCode, state} = route.location;

            if (!countryFeatureGroups.has(countryCode)) {
                countryFeatureGroups.set(countryCode, L.featureGroup());
            }

            const polyline = L.Polyline.fromEncoded(route.polyline).getLatLngs();
            L.polyline(polyline, {
                color: '#fc6719',
                weight: 1.5,
                opacity: 0.5,
                smoothFactor: 1,
                overrideExisting: true,
                detectColors: true,
            }).addTo(countryFeatureGroups.get(countryCode));

            if (mostActiveState === state) {
                L.polyline(polyline).addTo(fitMapBoundsFeatureGroup);
            }
        });

        countryFeatureGroups.forEach((featureGroup, countryCode) => {
            featureGroup.addTo(mainFeatureGroup);
            places.push({
                name: countryCode,
                bounds: featureGroup.getBounds()
            });
        });

        mainFeatureGroup.addTo(map);
        L.control.flyToPlaces({places}).addTo(map);

        map.fitBounds(fitMapBoundsFeatureGroup.getBounds());
    }

    return {
        render
    };
}