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
        formData.append('filters', JSON.stringify(filters));
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.data.message);
            }
        } catch (error) {
            console.error('Error al cargar órdenes:', error);
            return [];
        }
    }
    
    /**
     * Obtener orden por ID
     */
    async getById(id) {
        const formData = new FormData();
        formData.append('action', 'juzt_get_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', id);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : null;
        } catch (error) {
            console.error('Error al cargar orden:', error);
            return null;
        }
    }
    
    /**
     * Aprobar orden
     */
    async approve(id) {
        const formData = new FormData();
        formData.append('action', 'juzt_approve_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', id);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al aprobar orden:', error);
            return { success: false };
        }
    }
    
    /**
     * Rechazar orden
     */
    async reject(id, reason = '') {
        const formData = new FormData();
        formData.append('action', 'juzt_reject_order');
        formData.append('nonce', this.nonce);
        formData.append('order_id', id);
        formData.append('reason', reason);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al rechazar orden:', error);
            return { success: false };
        }
    }
    
    /**
     * Crear orden manualmente
     */
    async create(orderData) {
        const formData = new FormData();
        formData.append('action', 'juzt_create_order');
        formData.append('nonce', this.nonce);
        formData.append('order_data', JSON.stringify(orderData));
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al crear orden:', error);
            return { success: false };
        }
    }
}

export default OrderModel;