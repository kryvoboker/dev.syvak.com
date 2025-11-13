/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/assets/ts/**/*.ts',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        '../storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        require('preline')
    ],
}
