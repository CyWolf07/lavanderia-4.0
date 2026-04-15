import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const brandBlue = {
    50: '#eef8ff',
    100: '#d8eeff',
    200: '#b8ddff',
    300: '#8bc5ff',
    400: '#55a7ff',
    500: '#2e89f4',
    600: '#1d6fda',
    700: '#1958ad',
    800: '#19498d',
    900: '#193d73',
    950: '#11274a',
};

const brandGreen = {
    50: '#edfdf6',
    100: '#d2f8e5',
    200: '#a9eed0',
    300: '#74dfb3',
    400: '#3ec991',
    500: '#1fab74',
    600: '#14895e',
    700: '#126d4d',
    800: '#13563f',
    900: '#123f31',
    950: '#08241d',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                primary: brandBlue,
                sky: brandBlue,
                emerald: brandGreen,
                brand: {
                    ink: '#0c2742',
                    surface: '#f5fbfb',
                    line: '#d7e7ef',
                    blue: '#1d6fda',
                    green: '#14895e',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
