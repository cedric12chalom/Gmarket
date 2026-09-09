/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Primary: Rich Forest / Deep Emerald (#0A4D3C)
        primary: {
          50: '#F0F9F5',
          100: '#D9F2E6',
          200: '#B2E4CD',
          300: '#7FCEAA',
          400: '#4AB184',
          500: '#1D9163',
          600: '#0A4D3C', // Signature deep forest green
          700: '#084032',
          800: '#063227',
          900: '#04231B',
        },
        // Secondary / Mint: Vibrant Fresh Lime (#9FE870)
        secondary: {
          50: '#F7FDE8',
          100: '#EEFCC7',
          200: '#DCF996',
          300: '#C4F35C',
          400: '#9FE870', // Signature mint lime
          500: '#84CC16',
          600: '#65A30D',
          700: '#4D7C0F',
          800: '#3F6212',
          900: '#365314',
        },
        forest: {
          dark: '#07362A',
          DEFAULT: '#0A4D3C',
          light: '#13624E',
        },
        mint: {
          light: '#C5F79E',
          DEFAULT: '#9FE870',
          dark: '#7ED647',
        },
        promo: {
          pink: '#FDE8E8',
          peach: '#FEEFDD',
          blue: '#E0F2FE',
          purple: '#F3E8FF',
          purpleDark: '#3B0764',
        },
        earth: {
          50: '#F5F7F5',
          100: '#EBEFEB',
          200: '#D8DFD8',
          300: '#B8C4B8',
          400: '#8E9E8E',
          500: '#687768',
          600: '#4F5C4F',
          700: '#3C473C',
          800: '#2A332A',
          900: '#1A211A',
        },
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'Fraunces', 'serif'],
      },
      borderRadius: {
        '3xl': '1.75rem',
        '4xl': '2.25rem',
      },
    },
  },
  plugins: [],
}