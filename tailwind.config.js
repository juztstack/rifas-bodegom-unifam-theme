// tailwind.config.js
/** @type {import('tailwindcss').Config} */
module.exports = {
  // Asegúrate de incluir las rutas donde usas las clases de Tailwind
  content: [
    './**/*.php', // Archivos PHP de WordPress (por ejemplo, functions.php, archivos de plantilla)
    './views/**/*.twig', // Archivos de plantilla de Timber
    './src/**/*.js', // Archivos JavaScript
    // Añade cualquier otra ruta relevante de tu tema
  ],
  theme: {
    extend: {
      // Personalizaciones de tu tema
    },
  },
  plugins: [
    // Plugins de Tailwind
  ],
}