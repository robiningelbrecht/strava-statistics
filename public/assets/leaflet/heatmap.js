export default function Heatmap($heatmapWrapper) {
    const $heatmap = $heatmapWrapper.querySelector('[data-leaflet-routes]');

    const mainFeatureGroup = L.featureGroup();
    let placesControl = null;
    const map = L.map($heatmap, {
        scrollWheelZoom: false,
        minZoom: 1,
        maxZoom: 21,
    });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    const determineMostActiveState = (routes) => {
        const stateCounts = routes.reduce((counts, route) => {
            const state = route.location.state;
            if (state) counts[state] = (counts[state] || 0) + 1;
            return counts;
        }, {});

        return Object.keys(stateCounts).reduce((a, b) => stateCounts[a] > stateCounts[b] ? a : b, '');
    };

    const filterOnActiveRoutes = function (routes) {
        return routes.filter((route) => route.active);
    }

    const render = () => {
        const routes = JSON.parse($heatmap.getAttribute('data-leaflet-routes'));
        updateRoutes(filterOnActiveRoutes(routes));

        // Filter event listeners.
        const clickableFilters = $heatmapWrapper.querySelectorAll('input[type="checkbox"][data-heatmap-filter],input[type="radio"][data-heatmap-filter]');
        clickableFilters.forEach(element => {
            element.addEventListener('click', () => {
                updateRoutes([]);
            });
        });
    }

    const updateRoutes = (routes) => {
        // First reset map before adding routes and controls.
        mainFeatureGroup.clearLayers();
        if (placesControl) {
            map.removeControl(placesControl);
        }

        const places = [];
        const countryFeatureGroups = new Map();
        const fitMapBoundsFeatureGroup = L.featureGroup();
        const mostActiveState = determineMostActiveState(routes);

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

        placesControl = L.control.flyToPlaces({places});
        placesControl.addTo(map);

        if (fitMapBoundsFeatureGroup.getBounds().isValid()) {
            map.fitBounds(fitMapBoundsFeatureGroup.getBounds());
        }
    }

    return {
        render
    };
}