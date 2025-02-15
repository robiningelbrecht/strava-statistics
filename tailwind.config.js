/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.html.twig",
        "./public/app.php",
        "./public/*.html",
        "./public/assets/leaflet/leaflet.fly.to.places.js",
    ],
    theme: {
        extend: {
            fontSize: {
                xxs: '0.625rem',
            },
            colors: {
                'strava-orange': '#F26722',
                'grey-yo': '#cccccc'
            },
            aria: {
                asc: 'sort="ascending"',
                desc: 'sort="descending"',
            },
        },
    }
}