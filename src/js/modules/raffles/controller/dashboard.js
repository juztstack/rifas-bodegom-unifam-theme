class DashboardController {
    constructor(orderModel) {
        this.orderModel = orderModel;
    }
    
    data() {
        const orderModel = this.orderModel;
        
        return {
            orders: [],
            loading: false,
            filters: {
                status: '',
                search: ''
            },
            
            // Lifecycle
            async init() {
                console.log("📊 Dashboard inicializado");
                await this.loadOrders();
            },
            
            // Acciones
            async loadOrders() {
                this.loading = true;
                try {
                    this.orders = await orderModel.getAll(this.filters);
                    console.log("✅ Órdenes cargadas:", this.orders.length);
                } catch (error) {
                    console.error("❌ Error cargando órdenes:", error);
                    this.orders = [];
                }
                this.loading = false;
            },
            
            viewOrder(orderId) {
                // ✅ Usar window.RaffleAppAdmin
                window.RaffleAppAdmin.router.navigate(`/order/view/${orderId}`);
            },
            
            filterByStatus(status) {
                this.filters.status = status;
                this.loadOrders();
            },
            
            // Computed
            get filteredOrders() {
                let filtered = this.orders;
                
                if (this.filters.status) {
                    filtered = filtered.filter(o => o.status === this.filters.status);
                }
                
                if (this.filters.search) {
                    const search = this.filters.search.toLowerCase();
                    filtered = filtered.filter(o => 
                        o.order_number.toLowerCase().includes(search) ||
                        o.customer_name.toLowerCase().includes(search)
                    );
                }
                
                return filtered;
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

export default DashboardController;