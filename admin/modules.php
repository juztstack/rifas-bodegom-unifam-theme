<?php

//Require Ajax functions
require_once get_template_directory() . '/admin/raffle/functions/ajax.php';

/**Raffle modules**/
/**
 * Registrar página admin de Juzt Raffle
 */

add_action('admin_menu', 'juzt_raffle_admin_menu');

function juzt_raffle_admin_menu() {
    add_menu_page(
        'Juzt Raffle',              // Page title
        'Juzt Raffle',              // Menu title
        'manage_options',           // Capability
        'juzt-raffle',              // Menu slug
        'juzt_raffle_render_admin', // Callback function
        'dashicons-tickets-alt',    // Icon
        3                           // Position
    );
}

/**
 * Render la página admin
 */
function juzt_raffle_render_admin() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.'));
    }
    
    // Cargar el template principal
    require_once get_template_directory() . '/admin/raffle/raffle-admin.php';
}

/**
 * Ocultar UI de WordPress solo en nuestra página
 */
add_action('admin_head', 'juzt_raffle_hide_wp_ui');

function juzt_raffle_hide_wp_ui() {
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'toplevel_page_juzt-raffle') {
        ?>
        <style>
            /* Ocultar menú lateral y admin bar */
            #adminmenumain,
            #wpadminbar,
            #wpfooter {
                display: none !important;
            }
            
            /* Ajustar contenedor principal */
            #wpcontent {
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
            
            #wpbody,
            #wpbody-content {
                padding: 0 !important;
                padding-top: 0 !important;
            }
            
            /* Fullscreen */
            html.wp-toolbar {
                padding-top: 0 !important;
            }
        </style>
        <?php
    }
}

/**
 * Cargar assets solo en nuestra página admin
 */
add_action('admin_enqueue_scripts', 'juzt_raffle_admin_assets');

function juzt_raffle_admin_assets($hook) {

    // Solo cargar en nuestra página
    if ($hook != 'toplevel_page_juzt-raffle') {
        return;
    }
    
    // Nuestro JavaScript
    wp_enqueue_script(
        'juzt-raffle-admin-js',
        get_template_directory_uri() . '/dist/juzt.admin-raffles.scripts.js',
        array(),
        '1.0.0',
        true
    );
    
    // Tailwind CSS (si lo tienes compilado)
    wp_enqueue_style(
        'juzt-raffle-admin-css',
        get_template_directory_uri() . '/dist/juzt.admin-raffles.styles.css',
        array(),
        '1.0.0'
    );
    
    // Pasar datos PHP a JavaScript
    wp_localize_script('juzt-raffle-admin-js', 'juztRaffleAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('juzt_raffle_nonce'),
        'adminUrl' => admin_url('admin.php?page=juzt-raffle')
    ));
}

/**
 * Cargar WordPress Media Library en página admin
 */
add_action('admin_enqueue_scripts', 'juzt_raffle_enqueue_media');

function juzt_raffle_enqueue_media($hook) {
    if ($hook === 'toplevel_page_juzt-raffle') {
        // ✅ Cargar Media Library de WordPress
        wp_enqueue_media();
    }
}