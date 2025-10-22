class OrderController {
    constructor(orderModel) {
        this.orderModel = orderModel;
    }
    
    data() {
        const orderModel = this.orderModel;
        
        return {
            order: null,
            loading: false,
            
            async init() {
                console.log("📄 OrderController inicializado");
                
                // ✅ Usar window.RaffleAppAdmin
                const orderId = window.RaffleAppAdmin.router.getParam('id');
                console.log("Order ID desde ruta:", orderId);
                
                if (orderId) {
                    await this.loadOrder(orderId);
                }
            },
            
            async loadOrder(id) {
                this.loading = true;
                try {
                    this.order = await orderModel.getById(id);
                    console.log("✅ Orden cargada:", this.order);
                } catch (error) {
                    console.error("❌ Error cargando orden:", error);
                    this.order = null;
                }
                this.loading = false;
            },
            
            async approveOrder() {
                if (!confirm('¿Aprobar esta orden?')) return;
                
                this.loading = true;
                try {
                    const result = await orderModel.approve(this.order.id);
                    
                    if (result.success) {
                        alert('Orden aprobada exitosamente');
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error al aprobar orden');
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al aprobar orden');
                }
                this.loading = false;
            },
            
            async rejectOrder() {
                const reason = prompt('Motivo del rechazo (opcional):');
                if (reason === null) return;
                
                this.loading = true;
                try {
                    const result = await orderModel.reject(this.order.id, reason);
                    
                    if (result.success) {
                        alert('Orden rechazada');
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error al rechazar orden');
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al rechazar orden');
                }
                this.loading = false;
            },
            
            goBack() {
                // ✅ Usar window.RaffleAppAdmin
                window.RaffleAppAdmin.router.navigate('/dashboard');
            },
            
            // Helpers
            getStatusBadge(status) {
                const badges = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'approved': 'bg-blue-100 text-blue-800',
                    'completed': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },
            
            getStatusText(status) {
                const texts = {
                    'pending': 'Pendiente',
                    'approved': 'Aprobada',
                    'completed': 'Completada',
                    'rejected': 'Rechazada'
                };
                return texts[status] || status;
            }
        };
    }
}

export default OrderController;