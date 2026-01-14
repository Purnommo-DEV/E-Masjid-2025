/** @type {import('tailwindcss').Config} */
export default {
  content: [
    
    "./resources/views/**/*.blade.php",
    // khusus pastikan semua blade di folder masjid kebaca:
    "./resources/views/masjid/**/*.blade.php",
    "./resources/js/**/*.js",
    "./resources/js/**/*.vue",
    "./resources/js/**/*.jsx",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#0d6efd',
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: {
    themes: [
      "light",
      "dark",
      {
        mytheme: {
          "primary": "#0d6efd",
          "secondary": "#f6d860",
          "accent": "#37cdbe",
          "neutral": "#191D24",
          "base-100": "#ffffff",
          "base-200": "#f3f4f6",
          "base-300": "#d1d5db",
          "success": "#10b981",        // tambah ini biar btn-success hijau
          "error": "#ef4444",
        },
      },
    ],
    darkTheme: "dark",
    defaultTheme: "mytheme",   // tambah baris ini → langsung pakai mytheme
  }
}
