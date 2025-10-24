<?php

namespace EndrockTheme\Classes;

class JuztRaffleCtp
{
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
    }
    
    public function register_post_type() {
        
        $labels = array(
            'name'                  => 'Rifas',
            'singular_name'         => 'Rifa',
            'menu_name'             => 'Rifas',
            'add_new_item'          => 'Agregar Nueva Rifa',
            'edit_item'             => 'Editar Rifa',
            'view_item'             => 'Ver Rifa',
            'all_items'             => 'Todas las Rifas',
            'search_items'          => 'Buscar Rifas',
        );
        
        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => false,  // No mostrar en admin WP (usamos interfaz custom)
            'show_in_menu'          => false,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'rifa'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'supports'              => array('title', 'editor', 'thumbnail'),
            'show_in_rest'          => true,
        );
        
        register_post_type('raffle', $args);
    }
}
