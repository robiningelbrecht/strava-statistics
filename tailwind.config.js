/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./templates/**/*.html.twig",
        "./public/app.php",
        "./node_modules/flowbite/**/*.js"
    ],
    theme: {
        extend: {
            colors: {
                'strava-orange': '#F26722',
            },
            aria: {
                asc: 'sort="ascending"',
                desc: 'sort="descending"',
            },
        },
    },
    plugins: [
        require('flowbite/plugin')
    ]
}