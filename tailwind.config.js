/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', 'sans-serif'],
      },
      colors: {
        'main': 'var(--main-color)',
        'accent': 'var(--accent-color)',
        'background': 'var(--background)',
        'text': 'var(--text)',
        'card': 'var(--card)',
        'border': 'var(--border)',
      },
    },
  },
  plugins: [],
}
