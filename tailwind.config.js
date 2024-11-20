/** @type {import('tailwindcss').Config} */
import preset from './vendor/filament/support/tailwind.config.preset'
import colors from "tailwindcss/colors.js";

export default {
    darkMode: ['variant', '&:not(.light *)'],
    presets: [preset],
    content: [
        './app/**/*.php',
        './resources/**/*.html',
        './resources/**/*.js',
        './resources/**/*.php',
        './vendor/filament/**/*.blade.php',
        './app/Filament/**/*.php',
        './vendor/awcodes/filament-tiptap-editor/resources/**/*.blade.php',
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
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
        require('tailwind-scrollbar')({preferredStrategy: 'pseudoelements'}),
    ],
}

