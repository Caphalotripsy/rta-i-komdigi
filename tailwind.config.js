/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#0350A0',
        secondary: '#037FBF',
        accent: '#FCCA13',
      }
    },
  },
  plugins: [],
}
