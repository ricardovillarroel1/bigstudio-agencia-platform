const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Mantener la libreria BigStudio siempre disponible aunque ninguna vista
    // la use todavia. Asi puedes empezar a aplicar `class="bs-btn-primary"`
    // en cualquier vista sin recompilar la lista.
    safelist: [
        'bs-display',
        'bs-btn', 'bs-btn-primary', 'bs-btn-secondary', 'bs-btn-ghost',
        'bs-btn-neutral', 'bs-btn-danger', 'bs-btn-success',
        'bs-btn-sm', 'bs-btn-lg',
        'bs-badge', 'bs-badge-success', 'bs-badge-warning', 'bs-badge-danger',
        'bs-badge-neutral', 'bs-badge-brand', 'bs-badge-info',
        'bs-card', 'bs-card-header', 'bs-card-body',
        'bs-input', 'bs-label',
        'bs-progress', 'bs-progress-fill', 'bs-progress-thin',
        'bs-link',
        'bs-table',
        'bs-nav-link', 'bs-nav-link-icon',
        // Patrones de utilidades brand
        { pattern: /^(bg|text|border|ring)-brand-(50|100|200|300|400|500|600|700|800|900|950)$/ },
        { pattern: /^(bg|text|border|ring)-accent-(50|100|200|300|400|500|600|700|800|900)$/ },
        // Grid spans con breakpoints (para layouts dinamicos via Blade)
        { pattern: /^(md|lg|sm):col-span-(1|2|3|4|5|6|7|8|9|10|11|12)$/ },
        { pattern: /^col-span-(1|2|3|4|5|6|7|8|9|10|11|12|full)$/ },
        'bg-bs-gradient', 'bg-bs-gradient-soft',
        'shadow-bs-glow', 'shadow-bs-card',
        'rounded-bs',
        'font-display',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Tipografia oficial Big Studio - usar para titulos, KPIs, branding
                display: ['Mostin', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Paleta oficial Big Studio - escala completa generada desde
                // los 3 colores base: #FFC800 (amarillo), #FF9C00 (medio), #FF8100 (primario).
                brand: {
                    50:  '#FFF7EC',
                    100: '#FFEDD0',
                    200: '#FFD89C',
                    300: '#FFC069',
                    400: '#FFAB36',
                    500: '#FF9C00', // naranja medio (oficial)
                    600: '#FF8100', // naranja primario (oficial, CTA)
                    700: '#E67400',
                    800: '#B85B00',
                    900: '#8A4400',
                    950: '#5C2D00',
                },
                // Acento amarillo (highlights, gradiente)
                accent: {
                    50:  '#FFFBEB',
                    100: '#FFF5C5',
                    200: '#FFE98A',
                    300: '#FFDC4F',
                    400: '#FFD11A',
                    500: '#FFC800', // amarillo oficial
                    600: '#E6B400',
                    700: '#B38C00',
                    800: '#806400',
                    900: '#4D3C00',
                },
            },
            backgroundImage: {
                // Gradiente oficial BigStudio (amarillo -> naranja medio -> naranja)
                'bs-gradient': 'linear-gradient(135deg, #FFC800 0%, #FF9C00 50%, #FF8100 100%)',
                'bs-gradient-soft': 'linear-gradient(135deg, #FFF7EC 0%, #FFEDD0 100%)',
            },
            boxShadow: {
                'bs-glow': '0 0 0 4px rgba(255, 129, 0, 0.15)',
                'bs-card': '0 1px 3px rgba(17, 24, 39, 0.08), 0 1px 2px rgba(17, 24, 39, 0.04)',
            },
            borderRadius: {
                'bs': '10px',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
