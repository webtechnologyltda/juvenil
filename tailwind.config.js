/** @type {import('tailwindcss').Config} */
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import scrollbar from 'tailwind-scrollbar';
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
                primary: {
                    50: 'oklch(0.97717647058824 0.01395454545455 45.756)',
                    100: 'oklch(0.95035294117647 0.03272727272727 45.756)',
                    200: 'oklch(0.90547058823529 0.06318181818182 45.756)',
                    300: 'oklch(0.84047058823529 0.10604545454546 45.756)',
                    400: 'oklch(0.75352941176471 0.15027272727273 45.756)',
                    500: 'oklch(0.68270588235294 0.17009090909091 45.756)',
                    600: 'oklch(0.59782352941176 0.16913636363636 45.756)',
                    700: 'oklch(0.51494117647059 0.14940909090909 45.756)',
                    800: 'oklch(0.44611764705882 0.12331818181818 45.756)',
                    900: 'oklch(0.39458823529412 0.09963636363636 45.756)',
                    950: 'oklch(0.27788235294118 0.07136363636364 45.756)',
                },
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
