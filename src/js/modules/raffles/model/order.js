/**
 * Order Model - Manejo de órdenes con AJAX
 */

class OrderModel {
    constructor() {
        this.endpoint = juztRaffleAdmin.ajaxUrl;
        this.nonce = juztRaffleAdmin.nonce;
    }

    /**
     * Obtener todas las órdenes
     */
    async getAll(filters = {}) {
        const formData = new FormData();
        formData.append('action', 'juzt_get_orders');
        formData.append('nonce', this.nonce);

        // Agregar filtros si existen
        if (filters.status) {
            formData.append('status', filters.status);
        }

        if (filters.raffle_id) {
            formData.append('raffle_id', filters.raffle_id);
        }

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                console.error('Error al obtener órdenes:', data);
                return [];
            }
        } catch (error) {
            console.error('Error de red al obtener órdenes:', error);
            return [];
        }
    }

    /**
     * Obtener orden por ID
     */
    async getById(orderId) {
        const formData = new FormData();
        formData.append('action', 'juzt_get_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                console.error('Error al obtener orden:', data);
                return null;
            }
        } catch (error) {
            console.error('Error de red al obtener orden:', error);
            return null;
        }
    }

    /**
     * Aprobar orden y asignar números
     */
    async approve(orderId) {
        const formData = new FormData();
        formData.append('action', 'juzt_approve_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido',
                numbers: data.data?.numbers || []
            };
        } catch (error) {
            console.error('Error al aprobar orden:', error);
            return {
                success: false,
                message: 'Error de red al aprobar orden'
            };
        }
    }

    /**
     * Rechazar orden
     */
    async reject(orderId, reason) {
        const formData = new FormData();
        formData.append('action', 'juzt_reject_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('reason', reason);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al rechazar orden:', error);
            return {
                success: false,
                message: 'Error de red al rechazar orden'
            };
        }
    }

    /**
     * Verificar pago de una cuota
     */
    async verifyPayment(orderId, installmentNumber, notes = '') {
        const formData = new FormData();
        formData.append('action', 'juzt_verify_payment');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('installment_number', installmentNumber);
        formData.append('notes', notes);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al verificar pago:', error);
            return {
                success: false,
                message: 'Error de red al verificar pago'
            };
        }
    }

    /**
     * Rechazar pago de una cuota
     */
    async rejectPayment(orderId, installmentNumber, reason) {
        const formData = new FormData();
        formData.append('action', 'juzt_reject_payment');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('installment_number', installmentNumber);
        formData.append('reason', reason);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al rechazar pago:', error);
            return {
                success: false,
                message: 'Error de red al rechazar pago'
            };
        }
    }

    /**
     * Subir comprobante de pago adicional
     */
    async uploadPaymentProof(orderId, installmentNumber, file) {
        const formData = new FormData();
        formData.append('action', 'juzt_upload_payment_proof');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('installment_number', installmentNumber);
        formData.append('payment_proof', file);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido',
                file_url: data.data?.file_url || ''
            };
        } catch (error) {
            console.error('Error al subir comprobante:', error);
            return {
                success: false,
                message: 'Error de red al subir comprobante'
            };
        }
    }

    /**
     * Crear orden manualmente desde el admin
     */
    async create(orderData) {
        const formData = new FormData();
        formData.append('action', 'juzt_create_order');
        formData.append('nonce', this.nonce);
        formData.append('raffle_id', orderData.raffle_id);
        formData.append('customer_name', orderData.customer_name);
        formData.append('customer_email', orderData.customer_email);
        formData.append('customer_phone', orderData.customer_phone);
        formData.append('customer_address', orderData.customer_address || '');
        formData.append('ticket_quantity', orderData.ticket_quantity);
        formData.append('payment_installments', orderData.payment_installments || 1);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido',
                order_id: data.data?.order_id,
                order_number: data.data?.order_number
            };
        } catch (error) {
            console.error('Error al crear orden:', error);
            return {
                success: false,
                message: 'Error de red al crear orden'
            };
        }
    }

    /**
     * Actualizar orden
     */
    async update(orderId, orderData) {
        const formData = new FormData();
        formData.append('action', 'juzt_update_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('customer_name', orderData.customer_name);
        formData.append('customer_email', orderData.customer_email);
        formData.append('customer_phone', orderData.customer_phone);
        formData.append('customer_address', orderData.customer_address || '');

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al actualizar orden:', error);
            return {
                success: false,
                message: 'Error de red al actualizar orden'
            };
        }
    }

    async changeStatus(orderId, status, notes = '') {
        const formData = new FormData();
        formData.append('action', 'juzt_change_order_status');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('status', status);
        formData.append('notes', notes);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al cambiar estado:', error);
            return {
                success: false,
                message: 'Error de red al cambiar estado'
            };
        }
    }

    /**
     * Eliminar orden
     */
    async delete(orderId) {
        const formData = new FormData();
        formData.append('action', 'juzt_delete_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido'
            };
        } catch (error) {
            console.error('Error al eliminar orden:', error);
            return {
                success: false,
                message: 'Error de red al eliminar orden'
            };
        }
    }

    /**
 * Agregar comentario a la orden
 */
    async addComment(orderId, comment) {
        const formData = new FormData();
        formData.append('action', 'juzt_add_order_comment');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('comment', comment);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido',
                comment: data.data?.comment || null
            };
        } catch (error) {
            console.error('Error al agregar comentario:', error);
            return {
                success: false,
                message: 'Error de red al agregar comentario'
            };
        }
    }

    /**
     * Subir comprobante manualmente desde admin
     */
    async uploadPaymentProofAdmin(orderId, installmentNumber, file) {
        const formData = new FormData();
        formData.append('action', 'juzt_upload_payment_proof_admin');
        formData.append('nonce', this.nonce);
        formData.append('order_id', orderId);
        formData.append('installment_number', installmentNumber);
        formData.append('payment_proof', file);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            return {
                success: data.success,
                message: data.data?.message || data.data || 'Error desconocido',
                file_url: data.data?.file_url || ''
            };
        } catch (error) {
            console.error('Error al subir comprobante:', error);
            return {
                success: false,
                message: 'Error de red al subir comprobante'
            };
        }
    }
}

export default OrderModel;