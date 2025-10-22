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
    
    // TODO: Consultar CPT real
    // Por ahora, datos dummy
    $raffles = [
        [
            'id' => 1,
            'title' => 'Rifa iPhone 15 Pro Max',
            'content' => 'Increíble oportunidad de ganar el último iPhone',
            'featured_image' => 'https://via.placeholder.com/150',
            'price' => 100,
            'allow_installments' => true,
            'ticket_limit' => 1000,
            'tickets_sold' => 450,
            'status' => 'active',
            'created_at' => '2025-01-10 10:00:00'
        ],
        [
            'id' => 2,
            'title' => 'Rifa Casa en la Playa',
            'content' => 'Casa de ensueño en playa paradisíaca',
            'featured_image' => 'https://via.placeholder.com/150',
            'price' => 10000,
            'allow_installments' => true,
            'ticket_limit' => 100000,
            'tickets_sold' => 35000,
            'status' => 'active',
            'created_at' => '2025-01-05 14:30:00'
        ],
        [
            'id' => 3,
            'title' => 'Rifa MacBook Pro M3',
            'content' => 'La laptop más potente del mercado',
            'featured_image' => 'https://via.placeholder.com/150',
            'price' => 200,
            'allow_installments' => false,
            'ticket_limit' => 500,
            'tickets_sold' => 500,
            'status' => 'completed',
            'created_at' => '2024-12-20 09:00:00'
        ]
    ];
    
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
    
    // TODO: Consultar CPT real con get_post() y get_post_meta()
    // Por ahora, datos dummy
    $raffle = [
        'id' => $raffle_id,
        'title' => 'Rifa iPhone 15 Pro Max',
        'content' => 'Increíble oportunidad de ganar el último iPhone con todas sus características premium.',
        'price' => 100,
        'allow_installments' => true,
        'ticket_limit' => 1000,
        'status' => 'active',
        'gallery' => [
            'https://via.placeholder.com/400x300/FF6B6B/FFFFFF?text=Imagen+1',
            'https://via.placeholder.com/400x300/4ECDC4/FFFFFF?text=Imagen+2',
            'https://via.placeholder.com/400x300/45B7D1/FFFFFF?text=Imagen+3',
        ],
        'prizes' => [
            [
                'title' => 'Primer Premio',
                'description' => 'iPhone 15 Pro Max 256GB',
                'image' => 'https://via.placeholder.com/200/FF6B6B/FFFFFF?text=Premio+1',
                'detail' => 'Incluye funda y protector de pantalla'
            ],
            [
                'title' => 'Segundo Premio',
                'description' => 'AirPods Pro 2da Gen',
                'image' => 'https://via.placeholder.com/200/4ECDC4/FFFFFF?text=Premio+2',
                'detail' => 'Con cancelación de ruido activa'
            ],
            [
                'title' => 'Tercer Premio',
                'description' => 'Apple Watch Series 9',
                'image' => 'https://via.placeholder.com/200/45B7D1/FFFFFF?text=Premio+3',
                'detail' => 'GPS + Cellular'
            ]
        ]
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
    
    // TODO: Crear post real
    /*
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($raffle_data['title']),
        'post_content' => wp_kses_post($raffle_data['content']),
        'post_status'  => 'publish',
        'post_type'    => 'raffle',
    ]);
    
    if ($post_id) {
        update_post_meta($post_id, '_raffle_price', floatval($raffle_data['price']));
        update_post_meta($post_id, '_raffle_allow_installments', $raffle_data['allow_installments']);
        update_post_meta($post_id, '_raffle_ticket_limit', intval($raffle_data['ticket_limit']));
        update_post_meta($post_id, '_raffle_gallery', $raffle_data['gallery']);
        update_post_meta($post_id, '_raffle_prizes', $raffle_data['prizes']);
        update_post_meta($post_id, '_raffle_status', sanitize_text_field($raffle_data['status']));
        update_post_meta($post_id, '_raffle_tickets_sold', 0);
        
        wp_send_json_success([
            'message' => 'Rifa creada exitosamente',
            'raffle_id' => $post_id
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al crear rifa']);
    }
    */
    
    // Simulación por ahora
    wp_send_json_success([
        'message' => 'Rifa creada exitosamente (simulado)',
        'raffle_id' => rand(100, 999)
    ]);
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
    
    // TODO: Actualizar post real con wp_update_post() y update_post_meta()
    
    // Simulación
    wp_send_json_success([
        'message' => 'Rifa actualizada exitosamente (simulado)',
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
    
    // TODO: Eliminar con wp_delete_post($raffle_id, true)
    
    // Simulación
    wp_send_json_success([
        'message' => 'Rifa eliminada exitosamente (simulado)',
        'raffle_id' => $raffle_id
    ]);
}