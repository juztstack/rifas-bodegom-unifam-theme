<?php

namespace EndrockTheme\Classes;

/**
 * Clase para gestionar templates JSON
 */
class SectionBuilderTemplates {
  /**
   * Directorio de templates en el tema
   */
  private $theme_directory = 'templates';
  
  /**
   * Constructor
   */
  public function __construct() {
      // Inicialización
  }
  
  /**
   * Establecer directorio de templates en el tema
   */
  public function set_theme_directory($directory) {
      $this->theme_directory = $directory;
  }
  
  /**
   * Filtro para incluir la plantilla personalizada
   */
  public function template_include($template) {
      // Solo aplicar en páginas singulares
      if (!is_singular('page')) {
          return $template;
      }
      
      $post_id = get_the_ID();
      $json_template = get_post_meta($post_id, '_json_template', true);
      
      // Si no hay plantilla JSON seleccionada, usar la plantilla normal
      if (empty($json_template)) {
          return $template;
      }
      
      // Verificar si existe la plantilla JSON
      $json_template_path = $this->find_json_template_file($json_template);
      if (!$json_template_path) {
          return $template;
      }
      
      // Usar nuestra plantilla personalizada
      $custom_template = $this->find_page_template_file();
      
      if ($custom_template) {
          return $custom_template;
      }
      
      // Si no existe nuestra plantilla personalizada, usar la plantilla por defecto
      return $template;
  }
  
  /**
   * Encontrar archivo de plantilla JSON
   */
  public function find_json_template_file($template_name) {
      $possible_paths = [
          // Buscar en el tema activo
          get_template_directory() . "/{$this->theme_directory}/{$template_name}.json",
          
          // Buscar en el plugin
          SB_THEME_DIR . "templates/{$template_name}.json",
      ];
      
      // Permitir filtrar rutas
      $possible_paths = apply_filters('sections_builder_template_paths', $possible_paths, $template_name);
      
      // Encontrar el primer archivo que exista
      foreach ($possible_paths as $path) {
          if (file_exists($path)) {
              return $path;
          }
      }
      
      return false;
  }

  public function get_json_template($template_name) {
    $template = $this->find_json_template_file($template_name);

    if( $template != '') {
        return json_decode(file_get_contents($template), true);
    }
  }
  
  /**
   * Encontrar archivo de plantilla de página
   */
  public function find_page_template_file() {
      $possible_paths = [
          // Buscar en el tema activo
          get_template_directory() . '/json-page-template.php',
          
          // Buscar en el plugin
          SB_THEME_DIR . 'templates/json-page-template.php',
      ];
      
      // Permitir filtrar rutas
      $possible_paths = apply_filters('sections_builder_page_template_paths', $possible_paths);
      
      // Encontrar el primer archivo que exista
      foreach ($possible_paths as $path) {
          if (file_exists($path)) {
              return $path;
          }
      }
      
      return false;
  }
  
  /**
   * Registrar meta boxes para seleccionar plantillas
   */
  public function register_meta_boxes() {
      add_meta_box(
          'json_template_selector',
          'Plantilla JSON',
          [$this, 'render_meta_box'],
          ['post', 'page'],
          'side',
          'high'
      );
  }
  
  /**
   * Renderizar meta box
   */
  public function render_meta_box($post) {
      // Verificar nonce para seguridad
      wp_nonce_field('json_template_selector', 'json_template_selector_nonce');
      
      // Obtener plantilla actualmente seleccionada
      $current_template = get_post_meta($post->ID, '_json_template', true);
      
      // Obtener todas las plantillas JSON disponibles
      $templates = $this->get_available_templates();
      
      // Renderizar selector
      ?>
      <p>Selecciona una plantilla JSON para esta página:</p>
      <select name="json_template" id="json_template" class="widefat">
          <option value="">Ninguna (usar plantilla estándar)</option>
          <?php foreach ($templates as $template_id => $template) : ?>
              <option value="<?php echo esc_attr($template_id); ?>" <?php selected($current_template, $template_id); ?>>
                  <?php echo esc_html($template['name']); ?>
              </option>
          <?php endforeach; ?>
      </select>
      <?php
  }
  
  /**
   * Guardar meta box
   */
  public function save_meta_boxes($post_id) {
      // Verificaciones de seguridad
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
      if (!isset($_POST['json_template_selector_nonce']) || !wp_verify_nonce($_POST['json_template_selector_nonce'], 'json_template_selector')) return;
      if (!current_user_can('edit_post', $post_id)) return;
      
      // Guardar la selección de plantilla
      if (isset($_POST['json_template'])) {
          update_post_meta($post_id, '_json_template', sanitize_text_field($_POST['json_template']));
      }
  }
  
  /**
   * Obtener todas las plantillas disponibles
   */
  public function get_available_templates() {
      $templates = [];
      
      // Buscar en el tema
      $theme_templates_dir = get_template_directory() . "/{$this->theme_directory}";
      if (is_dir($theme_templates_dir)) {
          $this->scan_templates_directory($theme_templates_dir, $templates, 'theme');
      }
      
      // Buscar en el plugin
      $plugin_templates_dir = SB_THEME_DIR . 'templates';
      if (is_dir($plugin_templates_dir)) {
          $this->scan_templates_directory($plugin_templates_dir, $templates, 'plugin');
      }
      
      return $templates;
  }
  
  /**
   * Escanear directorio de plantillas
   */
  private function scan_templates_directory($directory, &$templates, $source) {
      $files = glob($directory . '/*.json');
      
      foreach ($files as $file) {
          $template_id = basename($file, '.json');
          $content = file_get_contents($file);
          $json_data = json_decode($content, true);
          
          if (json_last_error() === JSON_ERROR_NONE && isset($json_data['template'])) {
              $templates[$template_id] = [
                  'name' => isset($json_data['name']) ? $json_data['name'] : ucfirst(str_replace('-', ' ', $template_id)),
                  'description' => isset($json_data['description']) ? $json_data['description'] : '',
                  'path' => $file,
                  'source' => $source
              ];
          }
      }
  }
  
  /**
   * Renderizar la plantilla JSON actual
   */
  public static function render_current_template() {
      $post_id = get_the_ID();
      $template_name = get_post_meta($post_id, '_json_template', true);
      
      if (empty($template_name)) {
          the_content();
          return;
      }
      
      $instance = sections_builder()->templates;
      $template_file = $instance->find_json_template_file($template_name);
      
      if (!$template_file) {
          the_content();
          return;
      }
      
      $json_content = file_get_contents($template_file);
      $template_data = json_decode($json_content, true);
      
      if (json_last_error() !== JSON_ERROR_NONE || !isset($template_data['sections']) || !is_array($template_data['sections'])) {
          the_content();
          return;
      }
      
      // Determinar el orden de las secciones
      $sections_order = [];
      
      if (isset($template_data['order']) && is_array($template_data['order'])) {
          $sections_order = $template_data['order'];
      } else {
          $sections_order = array_keys($template_data['sections']);
      }
      
      echo '<div class="template-sections">';
      
      // Renderizar cada sección en el orden especificado
      foreach ($sections_order as $section_id) {
          if (!isset($template_data['sections'][$section_id])) {
              continue;
          }
          
          $section = $template_data['sections'][$section_id];
          $section['id'] = $section_id;
          
          echo SectionBuilderSections::render_section($section);
      }
      
      echo '</div>';
  }
}