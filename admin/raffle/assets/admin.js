/**
 * Alpine.js App para Juzt Raffle Admin
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('raffleApp', () => ({
        // Estado
        currentView: 'dashboard',
        selectedOrderId: null,
        orders: [],
        loading: false,
        
        // Inicialización
        init() {
            console.log('🎟️ Juzt Raffle Admin inicializado');
            this.loadOrders();
        },
        
        // Cambiar vista
        showView(view, params = {}) {
            this.currentView = view;
            
            if (params.orderId) {
                this.selectedOrderId = params.orderId;
            }
            
            console.log(`Vista actual: ${view}`);
        },
        
        // Cargar órdenes (simulado por ahora)
        async loadOrders() {
            this.loading = true;
            
            // Simular llamada AJAX
            setTimeout(() => {
                this.orders = [
                    {
                        id: 1,
                        order_number: 'RIFA-2025-001',
                        customer_name: 'Juan Pérez',
                        customer_email: 'juan@example.com',
                        raffle_title: 'Rifa iPhone 15 Pro',
                        ticket_quantity: 5,
                        total_amount: 500,
                        status: 'pending',
                        created_at: '2025-01-15 10:30:00'
                    },
                    {
                        id: 2,
                        order_number: 'RIFA-2025-002',
                        customer_name: 'María García',
                        customer_email: 'maria@example.com',
                        raffle_title: 'Rifa Casa en la Playa',
                        ticket_quantity: 10,
                        total_amount: 10000,
                        status: 'approved',
                        created_at: '2025-01-16 14:20:00'
                    },
                    {
                        id: 3,
                        order_number: 'RIFA-2025-003',
                        customer_name: 'Carlos Rodríguez',
                        customer_email: 'carlos@example.com',
                        raffle_title: 'Rifa iPhone 15 Pro',
                        ticket_quantity: 3,
                        total_amount: 300,
                        status: 'completed',
                        created_at: '2025-01-17 09:15:00'
                    }
                ];
                
                this.loading = false;
            }, 500);
        },
        
        // Ver detalle de orden
        viewOrder(orderId) {
            this.showView('order', { orderId });
        },
        
        // Obtener orden por ID
        getOrder(orderId) {
            return this.orders.find(order => order.id === orderId);
        },
        
        // Helpers de estado
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
    }));
});