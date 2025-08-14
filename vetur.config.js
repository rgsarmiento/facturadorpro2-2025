module.exports = {
  // Configuración específica para Vetur
  settings: {
    "vetur.useWorkspaceDependencies": true,
    "vetur.experimental.templateInterpolationService": true
  },

  // Rutas de los proyectos (para proyectos multi-carpeta)
  projects: [
    './modules', // Aquí están tus componentes Vue de Payroll
    './resources' // Recursos de Laravel
  ],

  // Configuración global
  globalComponents: [
    './modules/**/Resources/assets/js/components/**/*.vue'
  ]
}
