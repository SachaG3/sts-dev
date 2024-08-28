/** @type {import('tailwindcss').Config} */
export default {
    content: ["./resources/views/**/*.{html,js,php}"],
    theme: {
        extend: {},
    },
    plugins: [require("daisyui")],
}

