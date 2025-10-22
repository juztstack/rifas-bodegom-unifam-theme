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