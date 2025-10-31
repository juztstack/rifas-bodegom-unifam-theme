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

function juzt_get_orders_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    // Obtener filtros
    $filters = array();

    if (!empty($_POST['status'])) {
        $filters['status'] = sanitize_text_field($_POST['status']);
    }

    if (!empty($_POST['raffle_id'])) {
        $filters['raffle_id'] = intval($_POST['raffle_id']);
    }

    // Obtener órdenes desde la base de datos
    $db = Juzt_Raffle_Database::get_instance();
    $orders = $db->get_orders($filters);

    // Enriquecer con información de la rifa
    foreach ($orders as &$order) {
        $raffle_title = get_the_title($order['raffle_id']);
        $order['raffle_title'] = $raffle_title ?: 'Rifa #' . $order['raffle_id'];
    }

    wp_send_json_success($orders);
}

// ============================================
// OBTENER ORDEN POR ID
// ============================================

add_action('wp_ajax_juzt_get_order', 'juzt_get_order_handler');

function juzt_get_order_handler()
{
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

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Agregar información de la rifa
    $order['raffle_title'] = get_the_title($order['raffle_id']);
    $order['raffle_permalink'] = get_permalink($order['raffle_id']);

    // Agregar información del admin que aprobó (si existe)
    if (!empty($order['approved_by'])) {
        $user = get_userdata($order['approved_by']);
        $order['approved_by_name'] = $user ? $user->display_name : 'Usuario desconocido';
    }

    wp_send_json_success($order);
}

// ============================================
// APROBAR ORDEN
// ============================================

add_action('wp_ajax_juzt_approve_order', 'juzt_approve_order_handler');

function juzt_approve_order_handler()
{
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

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Verificar que todos los pagos estén verificados
    $all_verified = true;
    foreach ($order['payments'] as $payment) {
        if ($payment['status'] !== 'verified') {
            $all_verified = false;
            break;
        }
    }

    if (!$all_verified) {
        wp_send_json_error(['message' => 'Todos los pagos deben estar verificados antes de aprobar']);
        return;
    }

    // Verificar que haya números disponibles
    $available = $db->get_available_numbers_count($order['raffle_id']);

    if ($order['ticket_quantity'] > $available) {
        wp_send_json_error(['message' => "Solo hay {$available} números disponibles"]);
        return;
    }

    // Asignar números
    $result = $db->assign_numbers($order_id, $order['raffle_id'], $order['ticket_quantity']);

    if (!$result['success']) {
        wp_send_json_error(['message' => $result['error']]);
        return;
    }

    // Actualizar estado de la orden
    $user_id = get_current_user_id();
    $db->update_order_status($order_id, 'completed', $user_id);

    // TODO: Enviar email al cliente con los números asignados

    wp_send_json_success([
        'message' => 'Orden aprobada y números asignados exitosamente',
        'numbers' => $result['numbers']
    ]);
}

// ============================================
// RECHAZAR ORDEN
// ============================================

add_action('wp_ajax_juzt_reject_order', 'juzt_reject_order_handler');

function juzt_reject_order_handler()
{
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

    if (empty($reason)) {
        wp_send_json_error(['message' => 'Debe proporcionar un motivo de rechazo']);
        return;
    }

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Actualizar estado y agregar razón de rechazo
    $user_id = get_current_user_id();
    $db->update_order_status($order_id, 'rejected', $user_id);
    $db->add_rejection_reason($order_id, $reason);

    // TODO: Enviar email al cliente notificando el rechazo

    wp_send_json_success([
        'message' => 'Orden rechazada exitosamente'
    ]);
}

/**
 * Obtener todas las rifas
 */
add_action('wp_ajax_juzt_get_raffles', 'juzt_get_raffles_handler');

function juzt_get_raffles_handler()
{
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
 * Crear nueva orden (desde el admin manualmente)
 */
add_action('wp_ajax_juzt_create_order', 'juzt_create_order_handler');

function juzt_create_order_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    // Obtener datos del POST
    $order_data = array(
        'raffle_id' => isset($_POST['raffle_id']) ? intval($_POST['raffle_id']) : 0,
        'customer_name' => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
        'customer_email' => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
        'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
        'customer_address' => isset($_POST['customer_address']) ? sanitize_textarea_field($_POST['customer_address']) : '',
        'ticket_quantity' => isset($_POST['ticket_quantity']) ? intval($_POST['ticket_quantity']) : 0,
        'payment_installments' => isset($_POST['payment_installments']) ? intval($_POST['payment_installments']) : 1,
    );

    // Validaciones
    if (
        empty($order_data['raffle_id']) || empty($order_data['customer_name']) ||
        empty($order_data['customer_email']) || empty($order_data['ticket_quantity'])
    ) {
        wp_send_json_error(['message' => 'Faltan campos obligatorios']);
        return;
    }

    // Calcular total
    $raffle_price = floatval(get_post_meta($order_data['raffle_id'], '_raffle_price', true));
    $order_data['total_amount'] = $raffle_price * $order_data['ticket_quantity'];

    // Crear orden
    $db = Juzt_Raffle_Database::get_instance();
    $result = $db->create_order($order_data);

    if ($result['success']) {
        wp_send_json_success([
            'message' => 'Orden creada exitosamente',
            'order_id' => $result['order_id'],
            'order_number' => $result['order_number']
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al crear orden: ' . $result['error']]);
    }
}

/**
 * Verificar pago de una cuota
 */
add_action('wp_ajax_juzt_verify_payment', 'juzt_verify_payment_handler');

function juzt_verify_payment_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $installment_number = isset($_POST['installment_number']) ? intval($_POST['installment_number']) : 0;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    if (!$order_id || !$installment_number) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    $db = Juzt_Raffle_Database::get_instance();
    $user_id = get_current_user_id();

    $result = $db->verify_payment($order_id, $installment_number, $user_id, $notes);

    if ($result) {
        wp_send_json_success([
            'message' => "Pago de cuota #{$installment_number} verificado exitosamente"
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al verificar el pago']);
    }
}

/**
 * Subir comprobante de pago adicional (para cuotas posteriores)
 */
add_action('wp_ajax_juzt_upload_payment_proof', 'juzt_upload_payment_proof_handler');
add_action('wp_ajax_nopriv_juzt_upload_payment_proof', 'juzt_upload_payment_proof_handler');

function juzt_upload_payment_proof_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $installment_number = isset($_POST['installment_number']) ? intval($_POST['installment_number']) : 0;

    if (!$order_id || !$installment_number) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    // Verificar que hay archivo
    if (empty($_FILES['payment_proof'])) {
        wp_send_json_error(['message' => 'No se recibió ningún archivo']);
        return;
    }

    // Validar tipo de archivo
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
    $file_type = $_FILES['payment_proof']['type'];

    if (!in_array($file_type, $allowed_types)) {
        wp_send_json_error(['message' => 'Tipo de archivo no permitido. Solo imágenes o PDF']);
        return;
    }

    // Validar tamaño (máximo 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['payment_proof']['size'] > $max_size) {
        wp_send_json_error(['message' => 'El archivo es demasiado grande. Máximo 5MB']);
        return;
    }

    // Subir archivo a WordPress Media Library
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_upload('payment_proof', 0);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'Error al subir archivo: ' . $attachment_id->get_error_message()]);
        return;
    }

    // Obtener URL del archivo
    $file_url = wp_get_attachment_url($attachment_id);

    // Guardar en la base de datos
    $db = Juzt_Raffle_Database::get_instance();
    $result = $db->upload_payment_proof($order_id, $installment_number, $file_url);

    if ($result) {
        // TODO: Enviar email al admin notificando nuevo comprobante

        wp_send_json_success([
            'message' => 'Comprobante subido exitosamente',
            'file_url' => $file_url
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al guardar el comprobante']);
    }
}

/**
 * Obtener rifa por ID
 */
add_action('wp_ajax_juzt_get_raffle', 'juzt_get_raffle_handler');

function juzt_get_raffle_handler()
{
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
        'slug' => get_post_field('post_name', $raffle_id),
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

function juzt_create_raffle_handler()
{
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

function juzt_update_raffle_handler()
{
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

function juzt_delete_raffle_handler()
{
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

/**
 * Actualizar orden existente
 */
add_action('wp_ajax_juzt_update_order', 'juzt_update_order_handler');

function juzt_update_order_handler()
{
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

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Solo se pueden editar órdenes pendientes
    if ($order['status'] !== 'pending') {
        wp_send_json_error(['message' => 'Solo se pueden editar órdenes pendientes']);
        return;
    }

    global $wpdb;
    $table = Juzt_Raffle_Database::get_orders_table();

    // Datos a actualizar
    $update_data = array(
        'updated_at' => current_time('mysql')
    );

    // Campos editables
    if (isset($_POST['customer_name'])) {
        $update_data['customer_name'] = sanitize_text_field($_POST['customer_name']);
    }
    if (isset($_POST['customer_email'])) {
        $update_data['customer_email'] = sanitize_email($_POST['customer_email']);
    }
    if (isset($_POST['customer_phone'])) {
        $update_data['customer_phone'] = sanitize_text_field($_POST['customer_phone']);
    }
    if (isset($_POST['customer_address'])) {
        $update_data['customer_address'] = sanitize_textarea_field($_POST['customer_address']);
    }

    $updated = $wpdb->update(
        $table,
        $update_data,
        array('id' => $order_id),
        array('%s', '%s', '%s', '%s', '%s'),
        array('%d')
    );

    if ($updated !== false) {
        // Registrar en historial
        $db->add_history(
            $order_id,
            'order_updated',
            'Información del cliente actualizada',
            null,
            'user_' . get_current_user_id()
        );

        wp_send_json_success([
            'message' => 'Orden actualizada exitosamente'
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al actualizar orden']);
    }
}

/**
 * Cambiar estado de orden manualmente
 */
add_action('wp_ajax_juzt_change_order_status', 'juzt_change_order_status_handler');

function juzt_change_order_status_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    if (!$order_id || !$new_status) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    // Validar estados permitidos
    $allowed_statuses = ['pending', 'payment_complete', 'approved', 'completed', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        wp_send_json_error(['message' => 'Estado no válido']);
        return;
    }

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Actualizar estado
    $user_id = get_current_user_id();
    $updated = $db->update_order_status($order_id, $new_status, $user_id);

    if ($updated) {
        // Registrar en historial
        $description = "Estado cambiado manualmente a: {$new_status}";
        if ($notes) {
            $description .= " - Notas: {$notes}";
        }

        $db->add_history(
            $order_id,
            'status_changed_manually',
            $description,
            json_encode(['old_status' => $order['status'], 'new_status' => $new_status, 'notes' => $notes]),
            'user_' . $user_id
        );

        wp_send_json_success([
            'message' => 'Estado actualizado exitosamente'
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al actualizar estado']);
    }
}

/**
 * Eliminar orden
 */
add_action('wp_ajax_juzt_delete_order', 'juzt_delete_order_handler');

function juzt_delete_order_handler()
{
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

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Solo se pueden eliminar órdenes pendientes o rechazadas
    if (!in_array($order['status'], ['pending', 'rejected'])) {
        wp_send_json_error(['message' => 'Solo se pueden eliminar órdenes pendientes o rechazadas']);
        return;
    }

    global $wpdb;

    // Eliminar en orden correcto (por las foreign keys)
    $history_table = Juzt_Raffle_Database::get_history_table();
    $payments_table = Juzt_Raffle_Database::get_payments_table();
    $numbers_table = Juzt_Raffle_Database::get_numbers_table();
    $orders_table = Juzt_Raffle_Database::get_orders_table();

    // 1. Eliminar historial
    $wpdb->delete($history_table, array('order_id' => $order_id), array('%d'));

    // 2. Eliminar pagos
    $wpdb->delete($payments_table, array('order_id' => $order_id), array('%d'));

    // 3. Eliminar números (si los hay)
    $wpdb->delete($numbers_table, array('order_id' => $order_id), array('%d'));

    // 4. Eliminar orden
    $deleted = $wpdb->delete($orders_table, array('id' => $order_id), array('%d'));

    if ($deleted) {
        wp_send_json_success([
            'message' => 'Orden eliminada exitosamente'
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al eliminar orden']);
    }
}

/**
 * Agregar comentario/nota a la orden
 */
add_action('wp_ajax_juzt_add_order_comment', 'juzt_add_order_comment_handler');

function juzt_add_order_comment_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';

    if (!$order_id || empty($comment)) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    $db = Juzt_Raffle_Database::get_instance();
    $order = $db->get_order($order_id);

    if (!$order) {
        wp_send_json_error(['message' => 'Orden no encontrada']);
        return;
    }

    // Agregar comentario al historial
    $user = wp_get_current_user();
    $user_name = $user->display_name ?: $user->user_login;

    $result = $db->add_history(
        $order_id,
        'comment_added',
        $comment,
        null,
        'user_' . get_current_user_id()
    );

    if ($result) {
        wp_send_json_success([
            'message' => 'Comentario agregado exitosamente',
            'comment' => [
                'id' => $result,
                'description' => $comment,
                'created_by' => 'user_' . get_current_user_id(),
                'created_at' => current_time('mysql'),
                'action_type' => 'comment_added'
            ]
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al agregar comentario']);
    }
}

/**
 * Subir comprobante manualmente desde el admin
 */
add_action('wp_ajax_juzt_upload_payment_proof_admin', 'juzt_upload_payment_proof_admin_handler');

function juzt_upload_payment_proof_admin_handler()
{
    check_ajax_referer('juzt_raffle_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No tienes permisos']);
        return;
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $installment_number = isset($_POST['installment_number']) ? intval($_POST['installment_number']) : 0;

    if (!$order_id || !$installment_number) {
        wp_send_json_error(['message' => 'Datos inválidos']);
        return;
    }

    // Verificar que hay archivo
    if (empty($_FILES['payment_proof'])) {
        wp_send_json_error(['message' => 'No se recibió ningún archivo']);
        return;
    }

    // Validar tipo de archivo
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
    $file_type = $_FILES['payment_proof']['type'];

    if (!in_array($file_type, $allowed_types)) {
        wp_send_json_error(['message' => 'Tipo de archivo no permitido. Solo imágenes o PDF']);
        return;
    }

    // Validar tamaño (máximo 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['payment_proof']['size'] > $max_size) {
        wp_send_json_error(['message' => 'El archivo es demasiado grande. Máximo 5MB']);
        return;
    }

    // Subir archivo
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_upload('payment_proof', 0);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(['message' => 'Error al subir archivo: ' . $attachment_id->get_error_message()]);
        return;
    }

    // Obtener URL
    $file_url = wp_get_attachment_url($attachment_id);

    // Guardar en la base de datos
    $db = Juzt_Raffle_Database::get_instance();
    $result = $db->upload_payment_proof($order_id, $installment_number, $file_url);

    if ($result) {
        // Registrar en historial
        $user = wp_get_current_user();
        $db->add_history(
            $order_id,
            'payment_uploaded_admin',
            "Comprobante de cuota #{$installment_number} subido por admin",
            json_encode(array('installment' => $installment_number, 'proof_url' => $file_url)),
            'user_' . get_current_user_id()
        );

        wp_send_json_success([
            'message' => 'Comprobante subido exitosamente',
            'file_url' => $file_url
        ]);
    } else {
        wp_send_json_error(['message' => 'Error al guardar el comprobante']);
    }
}
