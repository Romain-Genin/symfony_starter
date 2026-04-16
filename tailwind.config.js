/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.html.twig',
        './assets/**/*.js',
        './src/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: 'var(--color-primary)',
                'primary-dark': 'var(--color-primary-dark)',
                secondary: 'var(--color-secondary)',
            },
            fontFamily: {
                sans: ['var(--font-family-base)', 'sans-serif'],
            },
            borderRadius: {
                DEFAULT: 'var(--border-radius)',
            },
        },
    },
    plugins: [],
};
