<?php

namespace EndrockTheme\Classes;

/**
 * Clase para el builder de plantillas JSON - Actualizada para Timber/Twig
 */
class SectionBuilderBuilder
{
    /**
     * Directorio para los assets del builder
     */
    private $assets_url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assets_url = SB_THEME_URL . '/assets/builder/';

        // Registrar hooks para el admin
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Registrar endpoints REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        $this->register_ajax_endpoints();
    }

    /**
     * Registrar endpoints AJAX
     */
    public function register_ajax_endpoints()
    {
        add_action('wp_ajax_get_templates', [$this, 'ajax_get_templates']);
        add_action('wp_ajax_get_sections', [$this, 'ajax_get_sections']);
        add_action('wp_ajax_get_section_schemas', [$this, 'ajax_get_section_schemas']);
        add_action('wp_ajax_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_save_template', [$this, 'ajax_save_template']);
    }

    /**
     * Endpoint AJAX para obtener plantillas
     */
    public function ajax_get_templates()
    {
        check_ajax_referer('sections_builder_nonce', 'nonce');

        $templates = [];

        // Buscar en el tema
        $theme_dir = get_template_directory() . '/templates';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['templates_directory'])) {
                $theme_dir = get_template_directory() . '/' . $config[0]['templates_directory'];
            }
        }

        if (is_dir($theme_dir)) {
            $files = glob($theme_dir . '/*.json');

            foreach ($files as $file) {
                $template_id = basename($file, '.json');
                $content = file_get_contents($file);
                $template_data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $templates[$template_id] = [
                        'name' => isset($template_data['name']) ? $template_data['name'] : ucfirst(str_replace('-', ' ', $template_id)),
                        'description' => isset($template_data['description']) ? $template_data['description'] : '',
                        'path' => $file,
                        'source' => 'theme',
                        'sections_count' => count($template_data['sections'] ?? [])
                    ];
                }
            }
        }

        wp_send_json_success($templates);
        exit;
    }

    /**
     * Endpoint AJAX para obtener secciones disponibles (ahora busca templates Twig)
     */
    public function ajax_get_sections()
    {
        check_ajax_referer('sections_builder_nonce', 'nonce');

        $sections = [];

        // Buscar templates Twig en views/sections/
        $views_sections_dir = get_template_directory() . '/views/sections';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['views_sections_directory'])) {
                $views_sections_dir = get_template_directory() . '/' . $config[0]['views_sections_directory'];
            }
        }

        error_log('=== DEBUG SECCIONES ===');
        error_log('Buscando templates Twig en: ' . $views_sections_dir);
        error_log('¿Directorio existe? ' . (is_dir($views_sections_dir) ? 'SÍ' : 'NO'));

        if (is_dir($views_sections_dir)) {
            $files = glob($views_sections_dir . '/*.twig');
            error_log('Archivos .twig encontrados: ' . count($files));

            foreach ($files as $file) {
                $section_id = basename($file, '.twig');

                // Ignorar archivos que comienzan con _ (parciales, helpers, etc)
                if (strpos($section_id, '_') === 0) {
                    continue;
                }

                // Debug: verificar existencia de esquema
                $has_schema = $this->section_schema_exists($section_id);
                $schema_path = $this->get_schema_path($section_id);

                error_log("Procesando sección: $section_id");
                error_log("  - Archivo Twig: $file");
                error_log("  - Ruta esquema esperada: $schema_path");
                error_log("  - ¿Esquema existe? " . ($has_schema ? 'SÍ' : 'NO'));

                // Obtener información del esquema si existe
                $schema_info = $this->get_section_schema_info($section_id);

                $sections[$section_id] = [
                    'id' => $section_id,
                    'name' => $schema_info['name'] ?? ucfirst(str_replace('-', ' ', $section_id)),
                    'description' => $schema_info['description'] ?? '',
                    'category' => $schema_info['category'] ?? 'general',
                    'icon' => $schema_info['icon'] ?? 'admin-generic',
                    'template_file' => basename($file),
                    'template_path' => $file,
                    'has_schema' => $has_schema,
                    'schema_path' => $schema_path,
                    'source' => 'theme'
                ];
            }
        } else {
            error_log('ERROR: El directorio de vistas no existe: ' . $views_sections_dir);
        }

        error_log('Total secciones encontradas: ' . count($sections));
        error_log('=== FIN DEBUG SECCIONES ===');

        wp_send_json_success($sections);
        exit;
    }

    /**
     * Endpoint AJAX para obtener esquemas de secciones (actualizado para nuevo formato)
     */
    public function ajax_get_section_schemas()
    {
        check_ajax_referer('sections_builder_nonce', 'nonce');

        $schemas = [];

        // Buscar archivos de esquema en schemas/
        $schemas_dir = get_template_directory() . '/schemas';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['schemas_directory'])) {
                $schemas_dir = get_template_directory() . '/' . $config[0]['schemas_directory'];
            }
        }

        error_log('=== DEBUG ESQUEMAS ===');
        error_log('Buscando esquemas en directorio: ' . $schemas_dir);
        error_log('¿Directorio existe? ' . (is_dir($schemas_dir) ? 'SÍ' : 'NO'));

        if (is_dir($schemas_dir)) {
            $files = glob($schemas_dir . '/*.php');
            error_log('Archivos .php encontrados en schemas: ' . count($files));

            foreach ($files as $file) {
                $section_id = basename($file, '.php');

                // Ignorar archivos que comienzan con _ (helpers, etc)
                if (strpos($section_id, '_') === 0) {
                    continue;
                }

                error_log('Procesando esquema: ' . $file . ' para sección: ' . $section_id);

                $schema_data = $this->parse_section_schema($section_id, $file);

                if ($schema_data) {
                    $schemas[$section_id] = [
                        'id' => $section_id,
                        'name' => $schema_data['name'],
                        'schema' => $schema_data,
                        'file' => $file,
                        'source' => 'theme'
                    ];

                    error_log('Esquema procesado exitosamente: ' . $section_id . ' con ' . count($schema_data['properties']) . ' propiedades');
                } else {
                    error_log('ERROR: No se pudo procesar el esquema para: ' . $section_id);
                }
            }
        } else {
            error_log('ERROR: Directorio de schemas no existe: ' . $schemas_dir);
        }

        error_log('Total schemas procesados exitosamente: ' . count($schemas));

        // Log detallado de cada esquema
        foreach ($schemas as $section_id => $schema_data) {
            error_log("- Esquema: " . $section_id);
            error_log("  - Nombre: " . $schema_data['schema']['name']);
            error_log("  - Propiedades: " . count($schema_data['schema']['properties']));

            // Log de propiedades individuales
            foreach ($schema_data['schema']['properties'] as $prop_key => $prop_data) {
                error_log("    * $prop_key: " . $prop_data['type'] . ' (' . $prop_data['title'] . ')');
            }
        }

        error_log('=== FIN DEBUG ESQUEMAS ===');

        wp_send_json_success($schemas);
        exit;
    }


    /**
     * Procesar definiciones de bloques del esquema
     */
    private function process_schema_blocks($blocks_config, $section_id)
    {
        $processed_blocks = [];

        foreach ($blocks_config as $block_id => $block_config) {
            if (!is_array($block_config)) {
                error_log("ADVERTENCIA: Configuración de bloque '$block_id' no es un array, ignorando");
                continue;
            }

            $block = [
                'id' => $block_id,
                'name' => $block_config['name'] ?? ucfirst(str_replace('-', ' ', $block_id)),
                'description' => $block_config['description'] ?? '',
                'icon' => $block_config['icon'] ?? 'admin-generic',
                'properties' => []
            ];

            // Convertir settings del bloque a properties
            if (isset($block_config['settings']) && is_array($block_config['settings'])) {
                $block['properties'] = $this->convert_settings_to_properties($block_config['settings']);
                error_log("Propiedades de bloque '$block_id' convertidas: " . count($block['properties']) . " campos");
            }

            // Configuraciones adicionales del bloque
            if (isset($block_config['limit'])) {
                $block['limit'] = (int)$block_config['limit'];
            }

            if (isset($block_config['min'])) {
                $block['min'] = (int)$block_config['min'];
            }

            if (isset($block_config['max'])) {
                $block['max'] = (int)$block_config['max'];
            }

            $processed_blocks[$block_id] = $block;
        }

        return $processed_blocks;
    }

    /**
     * Parsear un archivo de esquema para extraer configuración (actualizado con soporte completo para bloques)
     */
    private function parse_section_schema($section_id, $schema_path)
    {
        if (!file_exists($schema_path)) {
            error_log("ERROR: Archivo de esquema no encontrado: $schema_path");
            return null;
        }

        try {
            // Incluir el archivo PHP y obtener el array de configuración
            $schema_config = include $schema_path;

            // Verificar que el archivo retorna un array válido
            if (!is_array($schema_config)) {
                error_log("ERROR: El esquema en $schema_path no retorna un array válido. Tipo retornado: " . gettype($schema_config));
                return null;
            }

            error_log("Esquema cargado correctamente para $section_id: " . json_encode($schema_config, JSON_UNESCAPED_UNICODE));

            // Estructura base del esquema
            $schema = [
                'name' => $schema_config['name'] ?? ucfirst(str_replace('-', ' ', $section_id)),
                'description' => $schema_config['description'] ?? '',
                'category' => $schema_config['category'] ?? 'general',
                'icon' => $schema_config['icon'] ?? 'admin-generic',
                'tag' => $schema_config['tag'] ?? 'section',
                'properties' => [],
                'blocks' => []
            ];

            // Convertir settings de la sección a properties
            if (isset($schema_config['settings']) && is_array($schema_config['settings'])) {
                $schema['properties'] = $this->convert_settings_to_properties($schema_config['settings']);
                error_log("Propiedades de sección convertidas para $section_id: " . count($schema['properties']) . " campos");
            } else {
                error_log("ADVERTENCIA: No se encontraron 'settings' en el esquema de $section_id");
            }

            // Procesar bloques si existen
            if (isset($schema_config['blocks']) && is_array($schema_config['blocks'])) {
                $schema['blocks'] = $this->process_schema_blocks($schema_config['blocks'], $section_id);
                error_log("Bloques procesados para $section_id: " . count($schema['blocks']) . " bloques definidos");
            }

            return $schema;
        } catch (Exception $e) {
            error_log("ERROR al parsear esquema $schema_path: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        } catch (ParseError $e) {
            error_log("ERROR de sintaxis en esquema $schema_path: " . $e->getMessage());
            error_log("En línea: " . $e->getLine());
            return null;
        } catch (Error $e) {
            error_log("ERROR fatal en esquema $schema_path: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convertir configuración de settings al formato de properties esperado
     */
    private function convert_settings_to_properties($settings)
    {
        $properties = [];

        foreach ($settings as $key => $setting) {
            if (!is_array($setting)) {
                error_log("ADVERTENCIA: Setting '$key' no es un array, ignorando");
                continue;
            }

            // Mapear tipos del formato shopify al formato del builder
            $original_type = $setting['type'] ?? 'text';
            $type = $this->map_setting_type($original_type);

            $property = [
                'title' => $setting['label'] ?? $setting['title'] ?? $this->generate_field_title($key),
                'type' => $type,
                'description' => $setting['info'] ?? $setting['description'] ?? '',
                'default' => $setting['default'] ?? $this->get_default_value_by_type($type)
            ];

            // Manejar opciones/enum para select, radio, etc.
            if (isset($setting['options']) && is_array($setting['options'])) {
                // Si options es un array asociativo, extraer keys y values
                if ($this->is_associative_array($setting['options'])) {
                    $property['enum'] = array_keys($setting['options']);
                    $property['enumNames'] = array_values($setting['options']);
                } else {
                    // Si es array simple, usar los mismos valores
                    $property['enum'] = $setting['options'];
                }
            }

            // Configuraciones específicas según el tipo
            switch ($original_type) {
                case 'image':
                    $property['format'] = 'image';
                    break;

                case 'color':
                    $property['type'] = 'color';
                    break;

                case 'range':
                    $property['type'] = 'number';
                    if (isset($setting['min'])) $property['minimum'] = (float)$setting['min'];
                    if (isset($setting['max'])) $property['maximum'] = (float)$setting['max'];
                    if (isset($setting['step'])) $property['multipleOf'] = (float)$setting['step'];
                    break;

                case 'number':
                    if (isset($setting['min'])) $property['minimum'] = (float)$setting['min'];
                    if (isset($setting['max'])) $property['maximum'] = (float)$setting['max'];
                    break;

                case 'textarea':
                    $property['format'] = 'textarea';
                    break;

                case 'checkbox':
                    $property['type'] = 'boolean';
                    break;
            }

            // Placeholder o ayuda
            if (isset($setting['placeholder'])) {
                $property['placeholder'] = $setting['placeholder'];
            }

            // Campos requeridos
            if (isset($setting['required']) && $setting['required']) {
                $property['required'] = true;
            }

            error_log("Propiedad convertida: $key -> " . json_encode($property, JSON_UNESCAPED_UNICODE));

            $properties[$key] = $property;
        }

        return $properties;
    }

    /**
     * Mapear tipos de Shopify/settings al formato del builder
     */
    private function map_setting_type($shopify_type)
    {
        $type_map = [
            'text' => 'string',
            'textarea' => 'string',
            'image' => 'string',
            'number' => 'number',
            'range' => 'number',
            'checkbox' => 'boolean',
            'select' => 'string',
            'radio' => 'string',
            'color' => 'color',
            'file' => 'string',
            'collection' => 'string',
            'product' => 'string',
            'blog' => 'string',
            'page' => 'string',
            'link_list' => 'string',
            'url' => 'string',
            'video_url' => 'string',
            'richtext' => 'string',
            'html' => 'string'
        ];

        return isset($type_map[$shopify_type]) ? $type_map[$shopify_type] : 'string';
    }

    /**
     * Obtener valor por defecto según el tipo
     */
    private function get_default_value_by_type($type)
    {
        switch ($type) {
            case 'number':
                return 0;
            case 'boolean':
                return false;
            case 'array':
                return [];
            default:
                return '';
        }
    }

    /**
     * Verificar si un array es asociativo
     */
    private function is_associative_array($array)
    {
        if (!is_array($array) || array() === $array) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Generar título legible para el campo
     */
    private function generate_field_title($field_name)
    {
        // Convertir snake_case a título legible
        $title = str_replace('_', ' ', $field_name);
        return ucwords($title);
    }

    /**
     * Verificar si existe un esquema para la sección
     */
    private function section_schema_exists($section_id)
    {
        $schema_path = $this->get_schema_path($section_id);
        $exists = file_exists($schema_path);

        error_log("Verificando esquema para '$section_id':");
        error_log("  - Ruta: $schema_path");
        error_log("  - Existe: " . ($exists ? 'SÍ' : 'NO'));

        // Si no existe, verificar también si hay archivos con guiones o guiones bajos
        if (!$exists) {
            $alternatives = [
                str_replace('-', '_', $section_id),
                str_replace('_', '-', $section_id)
            ];

            foreach ($alternatives as $alt_id) {
                $alt_path = $this->get_schema_path($alt_id);
                if (file_exists($alt_path)) {
                    error_log("  - Encontrado esquema alternativo: $alt_path");
                    return true;
                }
            }
        }

        return $exists;
    }

    /**
     * Obtener la ruta completa al archivo de esquema
     */
    private function get_schema_path($section_id)
    {
        $schemas_dir = get_template_directory() . '/schemas';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['schemas_directory'])) {
                $schemas_dir = get_template_directory() . '/' . $config[0]['schemas_directory'];
            }
        }

        return $schemas_dir . '/' . $section_id . '.php';
    }

    /**
     * Obtener información básica de un esquema (actualizado)
     */
    private function get_section_schema_info($section_id)
    {
        $schema_path = $this->get_schema_path($section_id);

        if (!file_exists($schema_path)) {
            error_log("get_section_schema_info: Esquema no encontrado para $section_id en $schema_path");
            return [];
        }

        try {
            $schema_config = include $schema_path;

            if (!is_array($schema_config)) {
                error_log("get_section_schema_info: Esquema inválido para $section_id");
                return [];
            }

            return [
                'name' => $schema_config['name'] ?? ucfirst(str_replace('-', ' ', $section_id)),
                'description' => $schema_config['description'] ?? '',
                'category' => $schema_config['category'] ?? 'general',
                'icon' => $schema_config['icon'] ?? 'admin-generic'
            ];
        } catch (\Exception $e) {
            error_log("ERROR en get_section_schema_info para $section_id: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Endpoint AJAX para obtener una plantilla específica
     */
    public function ajax_get_template()
    {
        check_ajax_referer('sections_builder_nonce', 'nonce');

        $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';

        if (empty($template_name)) {
            wp_send_json_error(['message' => 'Nombre de plantilla no proporcionado']);
            exit;
        }

        // Buscar en el tema
        $theme_dir = get_template_directory() . '/templates';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['templates_directory'])) {
                $theme_dir = get_template_directory() . '/' . $config[0]['templates_directory'];
            }
        }

        $template_file = $theme_dir . '/' . $template_name . '.json';

        if (!file_exists($template_file)) {
            wp_send_json_error(['message' => 'Plantilla no encontrada']);
            exit;
        }

        $json_content = file_get_contents($template_file);
        $template_data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['message' => 'Error al decodificar el JSON de la plantilla']);
            exit;
        }

        // Adaptar estructura para Timber/Twig
        $template_data = $this->adapt_template_for_timber($template_data);

        wp_send_json_success($template_data);
        exit;
    }

    /**
     * Endpoint AJAX para guardar una plantilla (actualizado para Timber/Twig)
     */
    public function ajax_save_template()
    {
        check_ajax_referer('sections_builder_nonce', 'nonce');

        $template_name = isset($_POST['template_name']) ? sanitize_text_field($_POST['template_name']) : '';
        $template_data = isset($_POST['template_data']) ? $_POST['template_data'] : '';

        if (empty($template_name)) {
            wp_send_json_error(['message' => 'Nombre de plantilla no proporcionado']);
            exit;
        }

        if (empty($template_data)) {
            wp_send_json_error(['message' => 'Datos de plantilla no proporcionados']);
            exit;
        }

        // Si los datos vienen como string JSON, convertirlos a array
        if (is_string($template_data)) {
            error_log('Datos de plantilla recibidos: ' . substr($template_data, 0, 100) . '...');

            $template_data = json_decode(stripslashes($template_data), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Error JSON: ' . json_last_error_msg());
                wp_send_json_error(['message' => 'Error al decodificar los datos JSON de la plantilla: ' . json_last_error_msg()]);
                exit;
            }
        }

        // Adaptar estructura para Timber/Twig antes de guardar
        $template_data = $this->adapt_template_for_timber($template_data);

        // Determinar dónde guardar la plantilla
        $theme_dir = get_template_directory() . '/templates';

        // Verificar configuración personalizada
        if (current_theme_supports('sections-builder')) {
            $config = get_theme_support('sections-builder');
            if (is_array($config) && !empty($config[0]) && isset($config[0]['templates_directory'])) {
                $theme_dir = get_template_directory() . '/' . $config[0]['templates_directory'];
            }
        }

        error_log('Directorio del tema: ' . $theme_dir);

        // Asegurarse de que el directorio existe
        if (!is_dir($theme_dir)) {
            $dir_created = wp_mkdir_p($theme_dir);
            if (!$dir_created) {
                wp_send_json_error(['message' => 'No se pudo crear el directorio de plantillas']);
                exit;
            }
        }

        // Ruta completa al archivo
        $template_file = $theme_dir . '/' . $template_name . '.json';

        // Convertir datos a JSON formateado
        $json_content = json_encode($template_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Guardar archivo
        $result = file_put_contents($template_file, $json_content);

        if ($result === false) {
            wp_send_json_error(['message' => 'Error al guardar la plantilla. Verifica los permisos de escritura.']);
            exit;
        }

        wp_send_json_success([
            'message' => 'Plantilla guardada correctamente',
            'file_path' => $template_file,
            'bytes_written' => $result
        ]);
        exit;
    }

    /**
     * Obtener esquema de una sección por su ID
     */
    private function get_section_schema_by_id($section_id)
    {
        $schema_path = $this->get_schema_path($section_id);

        if (!file_exists($schema_path)) {
            return null;
        }

        return $this->parse_section_schema($section_id, $schema_path);
    }

    /**
     * Adaptar estructura de plantilla para Timber/Twig (actualizado con soporte para bloques)
     */
    private function adapt_template_for_timber($template)
    {
        // Asegurarse de que existe la estructura correcta
        if (!isset($template['order'])) {
            $template['order'] = array_keys($template['sections'] ?? []);
        }

        if (!isset($template['sections'])) {
            $template['sections'] = [];
        }

        // Convertir secciones al formato esperado por Timber
        foreach ($template['sections'] as $section_key => &$section) {
            // Asegurarse de que cada sección tiene section_id
            if (!isset($section['section_id'])) {
                $section['section_id'] = $section_key;
            }

            // Mantener compatibilidad con el formato anterior donde:
            // - section.settings contiene configuraciones de la sección
            // - section.blocks contiene los bloques

            // Si hay configuraciones directas en la sección, moverlas a 'settings'
            $section_schema = $this->get_section_schema_by_id($section['section_id']);
            if ($section_schema && isset($section_schema['properties'])) {
                if (!isset($section['settings'])) {
                    $section['settings'] = [];
                }

                // Mover configuraciones que corresponden al esquema de la sección a 'settings'
                foreach ($section_schema['properties'] as $prop_key => $prop_config) {
                    if (isset($section[$prop_key]) && !isset($section['settings'][$prop_key])) {
                        $section['settings'][$prop_key] = $section[$prop_key];
                    }
                }
            }

            // Asegurar que blocks existe como array
            if (!isset($section['blocks'])) {
                $section['blocks'] = [];
            }

            // Procesar bloques existentes para mantener estructura correcta
            if (is_array($section['blocks'])) {
                foreach ($section['blocks'] as $block_index => &$block) {
                    // Asegurar que cada bloque tiene un type/block_id
                    if (!isset($block['type']) && !isset($block['block_id'])) {
                        // Si no tiene tipo definido, intentar inferirlo o usar un valor por defecto
                        $block['type'] = 'default';
                    }

                    // Mantener settings del bloque
                    if (!isset($block['settings'])) {
                        $block['settings'] = [];
                    }
                }
            }
        }

        return $template;
    }

    /**
     * Registrar menú de administración
     */
    public function register_admin_menu()
    {
        add_menu_page(
            __('Template Builder', 'sections-builder'),
            __('Template Builder', 'sections-builder'),
            'manage_options',
            'sections-builder-templates',
            [$this, 'render_admin_page'],
            'dashicons-layout',
            30
        );
    }

    /**
     * Renderizar página de administración
     */
    public function render_admin_page()
    {
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Template Builder - Timber/Twig Edition', 'sections-builder'); ?></h1>
            <div id="sections-builder-app"></div>
        </div>
<?php
    }

    /**
     * Cargar scripts y estilos para el admin
     */
    public function enqueue_admin_assets($hook)
    {
        // Solo cargar en la página del builder
        if ($hook !== 'toplevel_page_sections-builder-templates') {
            return;
        }

        // Registrar y cargar estilos
        wp_enqueue_style(
            'sections-builder-styles',
            $this->assets_url . 'css/builder.css',
            [],
            SB_VERSION . '-' . time()
        );

        // Cargar media uploader de WordPress
        wp_enqueue_media();

        // Cargar script principal del builder
        wp_enqueue_script(
            'sections-builder-app',
            $this->assets_url . 'js/builder.js',
            ['jquery', 'wp-api-fetch'],
            SB_VERSION,
            true
        );

        // Pasar datos al script
        wp_localize_script(
            'sections-builder-app',
            'sectionsBuilderData',
            [
                'restUrl' => esc_url_raw(rest_url('sections-builder/v1')),
                'nonce' => wp_create_nonce('sections_builder_nonce'),
                'themeSupport' => current_theme_supports('sections-builder'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'pluginUrl' => SB_THEME_URL,
                'version' => SB_VERSION,
                'timberEnabled' => class_exists('Timber\Timber')
            ]
        );
    }

    /**
     * Registrar rutas de la REST API
     */
    public function register_rest_routes()
    {
        register_rest_route('sections-builder/v1', '/sections', [
            'methods' => 'GET',
            'callback' => function () {
                return ['message' => 'Secciones endpoint funcionando correctamente - Timber/Twig Edition'];
            },
            'permission_callback' => function () {
                return true;
            }
        ]);
    }

    /**
     * Verificar permisos de administrador
     */
    public function check_admin_permission()
    {
        return current_user_can('manage_options');
    }

    /**
     * Mapear tipos de campos del formato antiguo al formato nuevo
     */
    private function map_type($type)
    {
        $type_map = [
            'text' => 'string',
            'textarea' => 'string',
            'image' => 'string',
            'number' => 'number',
            'checkbox' => 'boolean',
            'select' => 'string',
            'color' => 'color',
            'radio' => 'string',
            'file' => 'string',
            'gallery' => 'array',
            'repeater' => 'array'
        ];

        return isset($type_map[$type]) ? $type_map[$type] : 'string';
    }

    /**
     * MÉTODO OBSOLETO - Ya no se usa extract_schema_properties
     * Mantenido para compatibilidad, pero devuelve array vacío
     */
    private function extract_schema_properties($content)
    {
        error_log("ADVERTENCIA: extract_schema_properties está obsoleto, usar convert_settings_to_properties");
        return [];
    }

    /**
     * Convertir valor por defecto según el tipo
     */
    private function convert_default_value($default_value, $type)
    {
        switch ($type) {
            case 'number':
                return is_numeric($default_value) ? (float)$default_value : 0;
            case 'boolean':
                return in_array(strtolower($default_value), ['true', '1', 'yes']);
            case 'array':
                if (strpos($default_value, 'array(') === 0) {
                    return [];
                }
                return $default_value;
            default:
                return (string)$default_value;
        }
    }

    /**
     * Determinar el tipo de campo basado en el nombre y valor por defecto
     */
    private function determine_field_type($field_name, $default_value)
    {
        // Casos específicos basados en el nombre
        if (strpos($field_name, 'color') !== false) {
            return 'color';
        }

        if (strpos($field_name, 'imagen') !== false || strpos($field_name, 'image') !== false) {
            return 'string'; // Se añadirá format: 'image' después
        }

        // Basado en el valor por defecto
        if (is_numeric($default_value)) {
            return 'number';
        }

        if (in_array(strtolower($default_value), ['true', 'false'])) {
            return 'boolean';
        }

        if (strpos($default_value, 'array(') === 0 || strpos($default_value, '[') === 0) {
            return 'array';
        }

        return 'string';
    }

    /**
     * Obtener opciones para ciertos campos
     */
    private function get_field_options($field_name)
    {
        $options_map = [
            'alineacion' => ['left', 'center', 'right'],
            'estilo_grid' => ['grid-2', 'grid-3', 'grid-4', 'grid-6'],
            'altura_seccion' => ['small', 'medium', 'large', 'screen'],
            'tipo_boton' => ['primary', 'secondary', 'outline'],
            'tamaño_texto' => ['small', 'medium', 'large', 'xl'],
            'posicion' => ['top', 'center', 'bottom'],
            'orientacion' => ['horizontal', 'vertical']
        ];

        foreach ($options_map as $pattern => $options) {
            if (strpos($field_name, $pattern) !== false) {
                return $options;
            }
        }

        return [];
    }
}
