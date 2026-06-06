/** @type {import('tailwindcss').Config} */
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import scrollbar from 'tailwind-scrollbar';
import colors from "tailwindcss/colors.js";

export default {
    darkMode: ['variant', '&:not(.light *)'],
    content: [
        './app/**/*.php',
        './resources/**/*.html',
        './resources/**/*.js',
        './resources/**/*.php',
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
    ],
    theme: {
        extend: {
            animation: {
                'spin-slow': 'bounce 20s ease-in infinite',
            },
            fontFamily: {
                poppins: ['Barlow', 'Poppins'],
                rustic: ['Rustic'],
            },
            colors: {
                'primary': colors.yellow,
            },
            backgroundImage: {
                'fundo-astronauta': "url('/img/Astronaut suit-pana.svg')",
            },
        },
    },
    plugins: [
        forms,
        typography,
        scrollbar({preferredStrategy: 'pseudoelements'}),
    ],
}
