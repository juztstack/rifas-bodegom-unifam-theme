<?php

/**
 * Clase para manejo de base de datos de Juzt Raffle
 */

if (!defined('ABSPATH')) {
    exit;
}

class Juzt_Raffle_Database
{
    const DB_VERSION = '1.1'; // ✅ Incrementar versión
    const DB_VERSION_OPTION = 'juzt_raffle_db_version';

    public function __construct()
    {
        add_action('after_setup_theme', array($this, 'check_database'));
    }

    public function check_database()
    {
        $installed_version = get_option(self::DB_VERSION_OPTION, '0');

        if (version_compare($installed_version, self::DB_VERSION, '<')) {
            $this->create_tables();
            update_option(self::DB_VERSION_OPTION, self::DB_VERSION);
        }
    }

    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $orders_table = $wpdb->prefix . 'raffle_orders';
        $numbers_table = $wpdb->prefix . 'raffle_numbers';
        $payments_table = $wpdb->prefix . 'raffle_order_payments'; // ✅ Nueva
        $history_table = $wpdb->prefix . 'raffle_order_history'; // ✅ Nueva

        // Tabla de órdenes
        $sql_orders = "CREATE TABLE {$orders_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number VARCHAR(50) NOT NULL,
            raffle_id BIGINT UNSIGNED NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) NOT NULL,
            customer_address TEXT,
            ticket_quantity INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_installments TINYINT DEFAULT 1,
            amount_paid DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            approved_by BIGINT UNSIGNED,
            approved_at DATETIME,
            rejection_reason TEXT,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY raffle_id (raffle_id),
            KEY status (status),
            KEY customer_email (customer_email),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabla de números
        $sql_numbers = "CREATE TABLE {$numbers_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            raffle_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            number VARCHAR(20) NOT NULL,
            assigned_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY raffle_id (raffle_id),
            KEY order_id (order_id),
            KEY number (number),
            UNIQUE KEY unique_raffle_number (raffle_id, number)
        ) $charset_collate;";

        // ✅ Tabla de pagos/cuotas
        $sql_payments = "CREATE TABLE {$payments_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            installment_number TINYINT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_proof_url VARCHAR(500),
            status VARCHAR(20) DEFAULT 'pending',
            uploaded_at DATETIME,
            verified_by BIGINT UNSIGNED,
            verified_at DATETIME,
            rejection_reason TEXT,
            notes TEXT,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY status (status),
            UNIQUE KEY unique_order_installment (order_id, installment_number)
        ) $charset_collate;";

        // ✅ Tabla de historial
        $sql_history = "CREATE TABLE {$history_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            description TEXT,
            meta_data TEXT,
            created_by VARCHAR(100),
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_orders);
        dbDelta($sql_numbers);
        dbDelta($sql_payments);
        dbDelta($sql_history);

        error_log('Juzt Raffle: Tablas actualizadas - Versión ' . self::DB_VERSION);
    }

    public static function drop_tables()
    {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'raffle_orders';
        $numbers_table = $wpdb->prefix . 'raffle_numbers';
        $payments_table = $wpdb->prefix . 'raffle_order_payments';
        $history_table = $wpdb->prefix . 'raffle_order_history';

        $wpdb->query("DROP TABLE IF EXISTS {$history_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$payments_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$numbers_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$orders_table}");

        delete_option(self::DB_VERSION_OPTION);
    }

    public static function get_orders_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'raffle_orders';
    }

    public static function get_numbers_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'raffle_numbers';
    }

    // ✅ Nuevos helpers
    public static function get_payments_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'raffle_order_payments';
    }

    public static function get_history_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'raffle_order_history';
    }

    // ============================================
    // MÉTODOS CRUD - ÓRDENES
    // ============================================

    public function create_order($data)
    {
        global $wpdb;
        $table = self::get_orders_table();

        $order_number = $this->generate_order_number();
        $installments = intval($data['payment_installments'] ?? 1);
        $total_amount = floatval($data['total_amount']);

        // Calcular monto de primera cuota
        $installment_amount = $installments > 1 ? round($total_amount / $installments, 2) : $total_amount;

        $order_data = array(
            'order_number' => $order_number,
            'raffle_id' => intval($data['raffle_id']),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => sanitize_text_field($data['customer_phone']),
            'customer_address' => sanitize_textarea_field($data['customer_address'] ?? ''),
            'ticket_quantity' => intval($data['ticket_quantity']),
            'total_amount' => $total_amount,
            'payment_installments' => $installments,
            'amount_paid' => 0, // Se actualiza cuando se verifican pagos
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        );

        $inserted = $wpdb->insert($table, $order_data);

        if ($inserted) {
            $order_id = $wpdb->insert_id;

            // ✅ Crear registros de pagos según cuotas
            $this->create_payment_installments($order_id, $installments, $total_amount);

            // ✅ Registrar en historial
            $this->add_history($order_id, 'order_created', 'Orden creada', null, 'customer');

            // ✅ Si hay comprobante inicial, registrar el primer pago
            if (!empty($data['payment_proof_url'])) {
                $this->upload_payment_proof($order_id, 1, $data['payment_proof_url']);
            }

            return array(
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number
            );
        }

        return array(
            'success' => false,
            'error' => $wpdb->last_error
        );
    }

    public function get_order($order_id)
    {
        global $wpdb;
        $table = self::get_orders_table();

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $order_id
        ), ARRAY_A);

        if ($order) {
            $order['numbers'] = $this->get_order_numbers($order_id);
            $order['payments'] = $this->get_order_payments($order_id); // ✅ Agregar pagos
            $order['history'] = $this->get_order_history($order_id); // ✅ Agregar historial
        }

        return $order;
    }

    public function get_orders($filters = array())
    {
        global $wpdb;
        $table = self::get_orders_table();

        $where = array('1=1');
        $params = array();

        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['raffle_id'])) {
            $where[] = 'raffle_id = %d';
            $params[] = intval($filters['raffle_id']);
        }

        if (!empty($filters['customer_email'])) {
            $where[] = 'customer_email = %s';
            $params[] = $filters['customer_email'];
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return $wpdb->get_results($query, ARRAY_A);
    }

    public function update_order_status($order_id, $status, $user_id = null)
    {
        global $wpdb;
        $table = self::get_orders_table();

        $update_data = array(
            'status' => $status,
            'updated_at' => current_time('mysql')
        );

        if ($status === 'approved' && $user_id) {
            $update_data['approved_by'] = $user_id;
            $update_data['approved_at'] = current_time('mysql');
        }

        $updated = $wpdb->update(
            $table,
            $update_data,
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );

        // ✅ Registrar en historial
        if ($updated !== false) {
            $this->add_history($order_id, 'status_changed', "Estado cambiado a: {$status}", null, $user_id ? "user_{$user_id}" : 'system');
        }

        return $updated !== false;
    }

    public function add_rejection_reason($order_id, $reason)
    {
        global $wpdb;
        $table = self::get_orders_table();

        $updated = $wpdb->update(
            $table,
            array('rejection_reason' => sanitize_textarea_field($reason)),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );

        // ✅ Registrar en historial
        if ($updated !== false) {
            $this->add_history($order_id, 'order_rejected', $reason, null, get_current_user_id());
        }

        return $updated;
    }

    // ============================================
    // MÉTODOS CRUD - PAGOS/CUOTAS
    // ============================================

    /**
     * Crear registros de cuotas al crear orden
     */
    public function create_payment_installments($order_id, $installments, $total_amount)
    {
        global $wpdb;
        $table = self::get_payments_table();

        // Calcular montos de cada cuota
        $installment_amount = round($total_amount / $installments, 2);
        $last_installment_amount = $total_amount - ($installment_amount * ($installments - 1));

        for ($i = 1; $i <= $installments; $i++) {
            $amount = ($i === $installments) ? $last_installment_amount : $installment_amount;

            $wpdb->insert(
                $table,
                array(
                    'order_id' => $order_id,
                    'installment_number' => $i,
                    'amount' => $amount,
                    'status' => 'pending'
                ),
                array('%d', '%d', '%f', '%s')
            );
        }
    }

    /**
     * Obtener pagos de una orden
     */
    public function get_order_payments($order_id)
    {
        global $wpdb;
        $table = self::get_payments_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE order_id = %d ORDER BY installment_number ASC",
            $order_id
        ), ARRAY_A);
    }

    /**
     * Subir comprobante de pago
     */
    public function upload_payment_proof($order_id, $installment_number, $proof_url)
    {
        global $wpdb;
        $table = self::get_payments_table();

        $updated = $wpdb->update(
            $table,
            array(
                'payment_proof_url' => esc_url_raw($proof_url),
                'uploaded_at' => current_time('mysql')
            ),
            array(
                'order_id' => $order_id,
                'installment_number' => $installment_number
            ),
            array('%s', '%s'),
            array('%d', '%d')
        );

        // ✅ Registrar en historial
        if ($updated !== false) {
            $this->add_history(
                $order_id,
                'payment_uploaded',
                "Comprobante de cuota #{$installment_number} subido",
                json_encode(array('installment' => $installment_number, 'proof_url' => $proof_url)),
                'customer'
            );
        }

        return $updated !== false;
    }

    /**
     * Verificar pago (admin)
     */
    public function verify_payment($order_id, $installment_number, $user_id, $notes = '')
    {
        global $wpdb;
        $table = self::get_payments_table();

        $updated = $wpdb->update(
            $table,
            array(
                'status' => 'verified',
                'verified_by' => $user_id,
                'verified_at' => current_time('mysql'),
                'notes' => sanitize_textarea_field($notes)
            ),
            array(
                'order_id' => $order_id,
                'installment_number' => $installment_number
            ),
            array('%s', '%d', '%s', '%s'),
            array('%d', '%d')
        );

        if ($updated !== false) {
            // Actualizar amount_paid en la orden
            $payment = $wpdb->get_row($wpdb->prepare(
                "SELECT amount FROM {$table} WHERE order_id = %d AND installment_number = %d",
                $order_id,
                $installment_number
            ));

            if ($payment) {
                $orders_table = self::get_orders_table();
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$orders_table} SET amount_paid = amount_paid + %f WHERE id = %d",
                    $payment->amount,
                    $order_id
                ));
            }

            // ✅ Registrar en historial
            $this->add_history(
                $order_id,
                'payment_verified',
                "Cuota #{$installment_number} verificada" . ($notes ? ": {$notes}" : ''),
                json_encode(array('installment' => $installment_number)),
                "user_{$user_id}"
            );

            // ✅ Verificar si todos los pagos están verificados
            $this->check_all_payments_verified($order_id);
        }

        return $updated !== false;
    }

    /**
     * Rechazar pago
     */
    public function reject_payment($order_id, $installment_number, $reason, $user_id)
    {
        global $wpdb;
        $table = self::get_payments_table();

        $updated = $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'rejection_reason' => sanitize_textarea_field($reason),
                'verified_by' => $user_id,
                'verified_at' => current_time('mysql')
            ),
            array(
                'order_id' => $order_id,
                'installment_number' => $installment_number
            ),
            array('%s', '%s', '%d', '%s'),
            array('%d', '%d')
        );

        if ($updated !== false) {
            $this->add_history(
                $order_id,
                'payment_rejected',
                "Cuota #{$installment_number} rechazada: {$reason}",
                json_encode(array('installment' => $installment_number, 'reason' => $reason)),
                "user_{$user_id}"
            );
        }

        return $updated !== false;
    }

    /**
     * Verificar si todos los pagos están verificados
     */
    private function check_all_payments_verified($order_id)
    {
        global $wpdb;
        $table = self::get_payments_table();

        // Contar pagos pendientes
        $pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE order_id = %d AND status = 'pending'",
            $order_id
        ));

        // Si no hay pagos pendientes, la orden puede ser aprobada
        if (intval($pending) === 0) {
            $this->update_order_status($order_id, 'payment_complete', get_current_user_id());
            $this->add_history($order_id, 'payments_complete', 'Todos los pagos verificados', null, 'system');
        }
    }

    // ============================================
    // MÉTODOS - HISTORIAL
    // ============================================

    public function add_history($order_id, $action_type, $description, $meta_data = null, $created_by = null)
    {
        global $wpdb;
        $table = self::get_history_table();

        return $wpdb->insert(
            $table,
            array(
                'order_id' => $order_id,
                'action_type' => $action_type,
                'description' => $description,
                'meta_data' => $meta_data,
                'created_by' => $created_by ?: 'system',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    public function get_order_history($order_id)
    {
        global $wpdb;
        $table = self::get_history_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE order_id = %d ORDER BY created_at DESC",
            $order_id
        ), ARRAY_A);
    }

    // ============================================
    // MÉTODOS CRUD - NÚMEROS
    // ============================================

    /**
     * Asignar números a una orden
     */
    public function assign_numbers($order_id, $raffle_id, $quantity)
    {
        global $wpdb;
        $table = self::get_numbers_table();

        // Generar números aleatorios únicos
        $numbers = $this->generate_random_numbers($raffle_id, $quantity);

        if (empty($numbers)) {
            return array(
                'success' => false,
                'error' => 'No hay suficientes números disponibles'
            );
        }

        $assigned = array();
        $current_time = current_time('mysql');

        foreach ($numbers as $number) {
            $inserted = $wpdb->insert(
                $table,
                array(
                    'raffle_id' => $raffle_id,
                    'order_id' => $order_id,
                    'number' => $number,
                    'assigned_at' => $current_time
                ),
                array('%d', '%d', '%s', '%s')
            );

            if ($inserted) {
                $assigned[] = $number;
            }
        }

        return array(
            'success' => true,
            'numbers' => $assigned
        );
    }

    /**
     * Obtener números de una orden
     */
    public function get_order_numbers($order_id)
    {
        global $wpdb;
        $table = self::get_numbers_table();

        return $wpdb->get_col($wpdb->prepare(
            "SELECT number FROM {$table} WHERE order_id = %d ORDER BY number ASC",
            $order_id
        ));
    }

    /**
     * Contar números disponibles de una rifa
     */
    public function get_available_numbers_count($raffle_id)
    {
        global $wpdb;
        $numbers_table = self::get_numbers_table();

        // Obtener límite de boletos de la rifa
        $ticket_limit = intval(get_post_meta($raffle_id, '_raffle_ticket_limit', true));

        // Contar números ya asignados
        $assigned_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$numbers_table} WHERE raffle_id = %d",
            $raffle_id
        ));

        return $ticket_limit - intval($assigned_count);
    }

    /**
     * Verificar si un número ya existe
     */
    public function check_number_exists($raffle_id, $number)
    {
        global $wpdb;
        $table = self::get_numbers_table();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE raffle_id = %d AND number = %s",
            $raffle_id,
            $number
        ));

        return intval($exists) > 0;
    }

    /**
     * Generar números aleatorios únicos
     */
    public function generate_random_numbers($raffle_id, $quantity)
    {
        $ticket_limit = intval(get_post_meta($raffle_id, '_raffle_ticket_limit', true));
        $available = $this->get_available_numbers_count($raffle_id);

        // Verificar que haya suficientes números disponibles
        if ($quantity > $available) {
            return array();
        }

        $numbers = array();
        $max_attempts = $quantity * 10; // Evitar loop infinito
        $attempts = 0;

        // Determinar padding (cantidad de ceros)
        $padding = strlen((string)($ticket_limit - 1));

        while (count($numbers) < $quantity && $attempts < $max_attempts) {
            $random_number = rand(0, $ticket_limit - 1);
            $formatted_number = str_pad($random_number, $padding, '0', STR_PAD_LEFT);

            // Verificar que no esté en el array y que no exista en BD
            if (!in_array($formatted_number, $numbers) && !$this->check_number_exists($raffle_id, $formatted_number)) {
                $numbers[] = $formatted_number;
            }

            $attempts++;
        }

        return $numbers;
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Generar número de orden único
     */
    public function generate_order_number()
    {
        global $wpdb;
        $table = self::get_orders_table();

        $year = date('Y');
        $attempts = 0;
        $max_attempts = 10;

        do {
            // Formato: RIFA-2025-0001
            $random = rand(1, 9999);
            $order_number = sprintf('RIFA-%s-%04d', $year, $random);

            // Verificar si existe
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE order_number = %s",
                $order_number
            ));

            $attempts++;
        } while ($exists > 0 && $attempts < $max_attempts);

        return $order_number;
    }
}

// Inicializar
new Juzt_Raffle_Database();
