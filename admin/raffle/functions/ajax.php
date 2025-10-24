<?php
/**
 * AJAX Handlers para Juzt Raffle
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// OBTENER TODAS LAS ÓRDENES
// ============================================

add_action('wp_ajax_juzt_get_orders', 'juzt_get_orders_handler');

function juzt_get_orders_handler() {
    // Verificar nonce
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    // TODO: Consultar base de datos real
    // Por ahora, datos dummy
    $orders = [
        [
            'id' => 1,
            'order_number' => 'RIFA-2025-001',
            'customer_name' => 'Juan Pérez',
            'customer_email' => 'juan@example.com',
            'raffle_title' => 'Rifa iPhone 15 Pro',
            'ticket_quantity' => 5,
            'total_amount' => 500,
            'status' => 'pending',
            'created_at' => '2025-01-15 10:30:00'
        ],
        [
            'id' => 2,
            'order_number' => 'RIFA-2025-002',
            'customer_name' => 'María García',
            'customer_email' => 'maria@example.com',
            'raffle_title' => 'Rifa Casa en la Playa',
            'ticket_quantity' => 10,
            'total_amount' => 10000,
            'status' => 'approved',
            'created_at' => '2025-01-16 14:20:00'
        ],
        [
            'id' => 3,
            'order_number' => 'RIFA-2025-003',
            'customer_name' => 'Carlos Rodríguez',
            'customer_email' => 'carlos@example.com',
            'raffle_title' => 'Rifa iPhone 15 Pro',
            'ticket_quantity' => 3,
            'total_amount' => 300,
            'status' => 'completed',
            'created_at' => '2025-01-17 09:15:00'
        ]
    ];
    
    wp_send_json_success($orders);
}

// ============================================
// OBTENER ORDEN POR ID
// ============================================

add_action('wp_ajax_juzt_get_order', 'juzt_get_order_handler');

function juzt_get_order_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de orden inválido']);
        return;
    }
    
    // TODO: Consultar base de datos real
    // Por ahora, datos dummy
    $order = [
        'id' => $order_id,
        'order_number' => 'RIFA-2025-00' . $order_id,
        'customer_name' => 'Juan Pérez',
        'customer_email' => 'juan@example.com',
        'customer_phone' => '+57 300 123 4567',
        'customer_address' => 'Calle 123 #45-67',
        'raffle_title' => 'Rifa iPhone 15 Pro',
        'ticket_quantity' => 5,
        'total_amount' => 500,
        'payment_installments' => 1,
        'status' => 'pending',
        'payment_proof_url' => 'https://via.placeholder.com/400x300',
        'created_at' => '2025-01-15 10:30:00'
    ];
    
    wp_send_json_success($order);
}

// ============================================
// APROBAR ORDEN
// ============================================

add_action('wp_ajax_juzt_approve_order', 'juzt_approve_order_handler');

function juzt_approve_order_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de orden inválido']);
        return;
    }
    
    // TODO: Actualizar en base de datos
    // TODO: Asignar números aleatorios
    // TODO: Enviar email al cliente
    
    wp_send_json_success([
        'message' => 'Orden aprobada exitosamente',
        'order_id' => $order_id
    ]);
}

// ============================================
// RECHAZAR ORDEN
// ============================================

add_action('wp_ajax_juzt_reject_order', 'juzt_reject_order_handler');

function juzt_reject_order_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
    
    if (!$order_id) {
        wp_send_json_error(['message' => 'ID de orden inválido']);
        return;
    }
    
    // TODO: Actualizar en base de datos
    // TODO: Enviar email al cliente con motivo
    
    wp_send_json_success([
        'message' => 'Orden rechazada',
        'order_id' => $order_id,
        'reason' => $reason
    ]);
}

/**
 * Obtener todas las rifas
 */
add_action('wp_ajax_juzt_get_raffles', 'juzt_get_raffles_handler');

function juzt_get_raffles_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    // Consultar rifas reales
    $args = [
        'post_type' => 'raffle',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    
    $query = new WP_Query($args);
    $raffles = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Obtener featured image
            $featured_image = get_the_post_thumbnail_url($post_id, 'thumbnail');
            
            $raffles[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'content' => get_the_content(),
                'featured_image' => $featured_image ?: '',
                'price' => floatval(get_post_meta($post_id, '_raffle_price', true)),
                'allow_installments' => (bool) get_post_meta($post_id, '_raffle_allow_installments', true),
                'ticket_limit' => intval(get_post_meta($post_id, '_raffle_ticket_limit', true)),
                'tickets_sold' => intval(get_post_meta($post_id, '_raffle_tickets_sold', true)),
                'status' => get_post_meta($post_id, '_raffle_status', true) ?: 'active',
                'created_at' => get_the_date('Y-m-d H:i:s'),
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success($raffles);
}

/**
 * Obtener rifa por ID
 */
add_action('wp_ajax_juzt_get_raffle', 'juzt_get_raffle_handler');

function juzt_get_raffle_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $raffle_id = isset($_POST['raffle_id']) ? intval($_POST['raffle_id']) : 0;
    
    if (!$raffle_id) {
        wp_send_json_error(['message' => 'ID de rifa inválido']);
        return;
    }
    
    $post = get_post($raffle_id);
    
    if (!$post || $post->post_type !== 'raffle') {
        wp_send_json_error(['message' => 'Rifa no encontrada']);
        return;
    }
    
    // Obtener galería
    $gallery_data = get_post_meta($raffle_id, '_raffle_gallery', true);
    $gallery = [];
    
    if (!empty($gallery_data) && is_array($gallery_data)) {
        foreach ($gallery_data as $item) {
            if (is_numeric($item)) {
                // Es un attachment ID
                $url = wp_get_attachment_url($item);
                if ($url) {
                    $gallery[] = $url;
                }
            } else {
                // Es una URL directa
                $gallery[] = $item;
            }
        }
    }
    
    // Obtener premios
    $prizes = get_post_meta($raffle_id, '_raffle_prizes', true);
    if (empty($prizes) || !is_array($prizes)) {
        // Al menos un premio vacío por defecto
        $prizes = [
            ['title' => '', 'description' => '', 'image' => '', 'detail' => '']
        ];
    }
    
    $raffle = [
        'id' => $raffle_id,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'price' => floatval(get_post_meta($raffle_id, '_raffle_price', true)),
        'allow_installments' => (bool) get_post_meta($raffle_id, '_raffle_allow_installments', true),
        'ticket_limit' => intval(get_post_meta($raffle_id, '_raffle_ticket_limit', true)),
        'status' => get_post_meta($raffle_id, '_raffle_status', true) ?: 'active',
        'gallery' => $gallery,
        'prizes' => $prizes,
    ];
    
    wp_send_json_success($raffle);
}

/**
 * Crear rifa
 */
add_action('wp_ajax_juzt_create_raffle', 'juzt_create_raffle_handler');

function juzt_create_raffle_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $raffle_data = isset($_POST['raffle_data']) ? json_decode(stripslashes($_POST['raffle_data']), true) : [];
    
    if (empty($raffle_data)) {
        wp_send_json_error(['message' => 'Datos de rifa inválidos']);
        return;
    }
    
    // Validar campos requeridos
    if (empty($raffle_data['title']) || !isset($raffle_data['price']) || !isset($raffle_data['ticket_limit'])) {
        wp_send_json_error(['message' => 'Faltan campos requeridos']);
        return;
    }
    
    // Crear el post
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($raffle_data['title']),
        'post_content' => wp_kses_post($raffle_data['content']),
        'post_status'  => 'publish',
        'post_type'    => 'raffle',
        'post_author'  => get_current_user_id(),
    ]);
    
    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Error al crear la rifa: ' . $post_id->get_error_message()]);
        return;
    }
    
    if ($post_id) {
        // Guardar meta fields
        update_post_meta($post_id, '_raffle_price', floatval($raffle_data['price']));
        update_post_meta($post_id, '_raffle_allow_installments', !empty($raffle_data['allow_installments']));
        update_post_meta($post_id, '_raffle_ticket_limit', intval($raffle_data['ticket_limit']));
        update_post_meta($post_id, '_raffle_status', sanitize_text_field($raffle_data['status']));
        update_post_meta($post_id, '_raffle_tickets_sold', 0);
        
        // Guardar galería de imágenes
        if (!empty($raffle_data['gallery']) && is_array($raffle_data['gallery'])) {
            // Convertir URLs a attachment IDs si es posible
            $gallery_ids = [];
            foreach ($raffle_data['gallery'] as $image_url) {
                $attachment_id = attachment_url_to_postid($image_url);
                if ($attachment_id) {
                    $gallery_ids[] = $attachment_id;
                } else {
                    // Si no se encuentra el ID, guardar la URL
                    $gallery_ids[] = $image_url;
                }
            }
            update_post_meta($post_id, '_raffle_gallery', $gallery_ids);
            
            // Establecer la primera imagen como featured image
            if (!empty($gallery_ids) && is_numeric($gallery_ids[0])) {
                set_post_thumbnail($post_id, $gallery_ids[0]);
            }
        }
        
        // Guardar premios
        if (!empty($raffle_data['prizes']) && is_array($raffle_data['prizes'])) {
            // Sanitizar premios
            $prizes = [];
            foreach ($raffle_data['prizes'] as $prize) {
                $prizes[] = [
                    'title' => sanitize_text_field($prize['title'] ?? ''),
                    'description' => sanitize_textarea_field($prize['description'] ?? ''),
                    'image' => esc_url_raw($prize['image'] ?? ''),
                    'detail' => sanitize_text_field($prize['detail'] ?? ''),
                ];
            }
            update_post_meta($post_id, '_raffle_prizes', $prizes);
        }
        
        wp_send_json_success([
            'message' => 'Rifa creada exitosamente',
            'raffle_id' => $post_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al crear la rifa']);
    }
}

/**
 * Actualizar rifa
 */
add_action('wp_ajax_juzt_update_raffle', 'juzt_update_raffle_handler');

function juzt_update_raffle_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $raffle_id = isset($_POST['raffle_id']) ? intval($_POST['raffle_id']) : 0;
    $raffle_data = isset($_POST['raffle_data']) ? json_decode(stripslashes($_POST['raffle_data']), true) : [];
    
    if (!$raffle_id || empty($raffle_data)) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }
    
    // Verificar que el post existe y es del tipo correcto
    $post = get_post($raffle_id);
    if (!$post || $post->post_type !== 'raffle') {
        wp_send_json_error(['message' => 'Rifa no encontrada']);
        return;
    }
    
    // Actualizar el post
    $updated = wp_update_post([
        'ID'           => $raffle_id,
        'post_title'   => sanitize_text_field($raffle_data['title']),
        'post_content' => wp_kses_post($raffle_data['content']),
    ]);
    
    if (is_wp_error($updated)) {
        wp_send_json_error(['message' => 'Error al actualizar: ' . $updated->get_error_message()]);
        return;
    }
    
    // Actualizar meta fields
    update_post_meta($raffle_id, '_raffle_price', floatval($raffle_data['price']));
    update_post_meta($raffle_id, '_raffle_allow_installments', !empty($raffle_data['allow_installments']));
    update_post_meta($raffle_id, '_raffle_ticket_limit', intval($raffle_data['ticket_limit']));
    update_post_meta($raffle_id, '_raffle_status', sanitize_text_field($raffle_data['status']));
    
    // Actualizar galería
    if (!empty($raffle_data['gallery']) && is_array($raffle_data['gallery'])) {
        $gallery_ids = [];
        foreach ($raffle_data['gallery'] as $image_url) {
            $attachment_id = attachment_url_to_postid($image_url);
            if ($attachment_id) {
                $gallery_ids[] = $attachment_id;
            } else {
                $gallery_ids[] = $image_url;
            }
        }
        update_post_meta($raffle_id, '_raffle_gallery', $gallery_ids);
        
        // Actualizar featured image
        if (!empty($gallery_ids) && is_numeric($gallery_ids[0])) {
            set_post_thumbnail($raffle_id, $gallery_ids[0]);
        }
    } else {
        delete_post_meta($raffle_id, '_raffle_gallery');
        delete_post_thumbnail($raffle_id);
    }
    
    // Actualizar premios
    if (!empty($raffle_data['prizes']) && is_array($raffle_data['prizes'])) {
        $prizes = [];
        foreach ($raffle_data['prizes'] as $prize) {
            $prizes[] = [
                'title' => sanitize_text_field($prize['title'] ?? ''),
                'description' => sanitize_textarea_field($prize['description'] ?? ''),
                'image' => esc_url_raw($prize['image'] ?? ''),
                'detail' => sanitize_text_field($prize['detail'] ?? ''),
            ];
        }
        update_post_meta($raffle_id, '_raffle_prizes', $prizes);
    }
    
    wp_send_json_success([
        'message' => 'Rifa actualizada exitosamente',
        'raffle_id' => $raffle_id
    ]);
}

/**
 * Eliminar rifa
 */
add_action('wp_ajax_juzt_delete_raffle', 'juzt_delete_raffle_handler');

function juzt_delete_raffle_handler() {
    check_ajax_referer('juzt_raffle_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }
    
    $raffle_id = isset($_POST['raffle_id']) ? intval($_POST['raffle_id']) : 0;
    
    if (!$raffle_id) {
        wp_send_json_error(['message' => 'ID de rifa inválido']);
        return;
    }
    
    $post = get_post($raffle_id);
    
    if (!$post || $post->post_type !== 'raffle') {
        wp_send_json_error(['message' => 'Rifa no encontrada']);
        return;
    }
    
    // Eliminar permanentemente (usa true para forzar eliminación)
    $deleted = wp_delete_post($raffle_id, true);
    
    if ($deleted) {
        wp_send_json_success([
            'message' => 'Rifa eliminada exitosamente',
            'raffle_id' => $raffle_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al eliminar la rifa']);
    }
}