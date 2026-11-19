/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./src/**/*.{html,ts}'],
  theme: {
    extend: {
      colors: {
        azul: {
          500: '#3b82f6',
          900: '#1e3a8a'
        },
        gris: '#f3f4f6'
      }
    }
  },
  plugins: []
};
