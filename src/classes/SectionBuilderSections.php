<?php

namespace EndrockTheme\Classes;


/**
 * Clase para gestionar secciones
 */
class SectionBuilderSections {
  /**
   * Directorio de secciones en el tema
   */
  private $theme_directory = 'sections';
  
  /**
   * Constructor
   */
  public function __construct() {
      // Inicialización
  }
  
  /**
   * Establecer directorio de secciones en el tema
   */
  public function set_theme_directory($directory) {
      $this->theme_directory = $directory;
  }
  
  /**
   * Encontrar archivo de sección
   */
  public function find_section_file($section_id) {
      $possible_paths = [
          // Buscar en el tema activo
          get_template_directory() . "/{$this->theme_directory}/{$section_id}.php",
          get_template_directory() . "/{$this->theme_directory}/{$section_id}/{$section_id}.php",
          
          // Buscar en el plugin
          SB_THEME_DIR . "sections/{$section_id}.php",
          SB_THEME_DIR . "sections/{$section_id}/{$section_id}.php",
      ];
      
      // Permitir filtrar rutas
      $possible_paths = apply_filters('sections_builder_section_paths', $possible_paths, $section_id);
      
      // Encontrar el primer archivo que exista
      foreach ($possible_paths as $path) {
          if (file_exists($path)) {
              return $path;
          }
      }
      
      return false;
  }
  
  /**
   * Renderizar una sección
   */
  public static function render_section($section) {
      $instance = sections_builder()->sections;
      
      if (!isset($section['section_id'])) {
          return '';
      }
      
      $section_id = $section['section_id'];
      $settings = isset($section['settings']) ? $section['settings'] : [];
      $blocks = isset($section['blocks']) ? $section['blocks'] : [];
      
      // Buscar el archivo de sección
      $section_file = $instance->find_section_file($section_id);
      
      if (!$section_file) {
          return '<div class="section-not-found">Sección no encontrada: ' . esc_html($section_id) . '</div>';
      }
      
      ob_start();
      
      echo '<div class="section section-' . esc_attr($section_id) . '" id="section-' . esc_attr($section['id']) . '">';
      echo '<div class="container">';
      
      // Incluir el archivo de sección
      include $section_file;
      
      echo '</div>'; // .container
      echo '</div>'; // .section
      
      return ob_get_clean();
  }
}

/**
* Helper para acceder a la instancia de Sections
*/
function sections_builder() {
  return \EndrockTheme\Classes\SectionBuilderCore::$instance;
}