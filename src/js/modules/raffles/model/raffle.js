class RaffleModel {
    constructor() {
        this.endpoint = juztRaffleAdmin.ajaxUrl;
        this.nonce = juztRaffleAdmin.nonce;
    }
    
    /**
     * Obtener todas las rifas
     */
    async getAll() {
        const formData = new FormData();
        formData.append('action', 'juzt_get_raffles');
        formData.append('nonce', this.nonce);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : [];
        } catch (error) {
            console.error('Error al cargar rifas:', error);
            return [];
        }
    }
    
    /**
     * Obtener rifa por ID
     */
    async getById(id) {
        const formData = new FormData();
        formData.append('action', 'juzt_get_raffle');
        formData.append('nonce', this.nonce);
        formData.append('raffle_id', id);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            return data.success ? data.data : null;
        } catch (error) {
            console.error('Error al cargar rifa:', error);
            return null;
        }
    }
    
    /**
     * Crear rifa
     */
    async create(raffleData) {
        const formData = new FormData();
        formData.append('action', 'juzt_create_raffle');
        formData.append('nonce', this.nonce);
        formData.append('raffle_data', JSON.stringify(raffleData));
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al crear rifa:', error);
            return { success: false };
        }
    }
    
    /**
     * Actualizar rifa
     */
    async update(id, raffleData) {
        const formData = new FormData();
        formData.append('action', 'juzt_update_raffle');
        formData.append('nonce', this.nonce);
        formData.append('raffle_id', id);
        formData.append('raffle_data', JSON.stringify(raffleData));
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al actualizar rifa:', error);
            return { success: false };
        }
    }
    
    /**
     * Eliminar rifa
     */
    async delete(id) {
        const formData = new FormData();
        formData.append('action', 'juzt_delete_raffle');
        formData.append('nonce', this.nonce);
        formData.append('raffle_id', id);
        
        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error al eliminar rifa:', error);
            return { success: false };
        }
    }
}

export default RaffleModel;