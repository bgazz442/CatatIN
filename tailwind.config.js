/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          DEFAULT: '#6D5CE8',
          600: '#5c4cd4',
          700: '#4d3ebb',
          800: '#3f3299',
          900: '#342a7c',
        },
        bgmain: '#F8FAFC',
        surface: '#FFFFFF',
        textprimary: '#1F2937',
        textsecondary: '#64748B',
        bordercolor: '#E5E7EB',
      },
      fontFamily: {
        sans: ['Inter', 'Poppins', 'system-ui', '-apple-system', 'sans-serif'],
      },
      borderRadius: {
        'card': '16px',
        'inner': '12px',
      },
      boxShadow: {
        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025)',
        'soft-hover': '0 10px 25px -5px rgba(109, 92, 232, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.04)',
      }
    },
  },
  plugins: [],
}
