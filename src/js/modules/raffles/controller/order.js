/**
 * Controller para gestión de órdenes
 */

class OrderController {
    constructor(orderModel) {
        this.orderModel = orderModel;
    }
    
    /**
     * Controller para vista de detalle de orden
     */
    data() {
        const orderModel = this.orderModel;
        
        return {
            order: null,
            loading: false,
            processing: false,
            
            init() {
                console.log("📄 OrderController inicializado");
                this.checkAndLoadOrder();
                
                window.addEventListener('route-changed', (e) => {
                    if (e.detail.view === 'order-detail' && e.detail.params.id) {
                        this.checkAndLoadOrder();
                    }
                });
            },
            
            checkAndLoadOrder() {
                const orderId = window.RaffleAppAdmin.router.getParam('id');
                console.log("Order ID desde ruta:", orderId);
                
                if (orderId && orderId !== this.order?.id) {
                    this.loadOrder(orderId);
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
                if (!confirm('¿Aprobar esta orden y asignar números?')) return;
                
                this.processing = true;
                try {
                    const result = await orderModel.approve(this.order.id);
                    
                    if (result.success) {
                        alert(`Orden aprobada exitosamente. Números asignados: ${result.numbers.join(', ')}`);
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error al aprobar orden: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al aprobar orden');
                }
                this.processing = false;
            },
            
            async rejectOrder() {
                const reason = prompt('Motivo del rechazo:');
                if (!reason) return;
                
                this.processing = true;
                try {
                    const result = await orderModel.reject(this.order.id, reason);
                    
                    if (result.success) {
                        alert('Orden rechazada exitosamente');
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error al rechazar orden: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al rechazar orden');
                }
                this.processing = false;
            },
            
            async verifyPayment(installmentNumber) {
                const notes = prompt('Notas (opcional):');
                if (notes === null) return; // User cancelled
                
                this.processing = true;
                try {
                    const result = await orderModel.verifyPayment(this.order.id, installmentNumber, notes);
                    
                    if (result.success) {
                        alert(result.message);
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al verificar pago');
                }
                this.processing = false;
            },
            
            async rejectPayment(installmentNumber) {
                const reason = prompt('Motivo del rechazo:');
                if (!reason) return;
                
                this.processing = true;
                try {
                    const result = await orderModel.rejectPayment(this.order.id, installmentNumber, reason);
                    
                    if (result.success) {
                        alert(result.message);
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al rechazar pago');
                }
                this.processing = false;
            },
            
            goBack() {
                window.RaffleAppAdmin.router.navigate('/dashboard');
            },
            
            // Helpers para badges
            getStatusBadge(status) {
                const badges = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'payment_complete': 'bg-blue-100 text-blue-800',
                    'approved': 'bg-green-100 text-green-800',
                    'completed': 'bg-green-600 text-white',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },
            
            getStatusText(status) {
                const texts = {
                    'pending': 'Pendiente',
                    'payment_complete': 'Pagos Completos',
                    'approved': 'Aprobada',
                    'completed': 'Completada',
                    'rejected': 'Rechazada'
                };
                return texts[status] || status;
            },
            
            getPaymentStatusBadge(status) {
                const badges = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'verified': 'bg-green-100 text-green-800',
                    'rejected': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },
            
            getPaymentStatusText(status) {
                const texts = {
                    'pending': 'Pendiente',
                    'verified': 'Verificado',
                    'rejected': 'Rechazado'
                };
                return texts[status] || status;
            },
            
            formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('es-ES', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            
            formatCurrency(amount) {
                return new Intl.NumberFormat('es-CO', {
                    style: 'currency',
                    currency: 'COP',
                    minimumFractionDigits: 0
                }).format(amount);
            }
        };
    }
}

export default OrderController;