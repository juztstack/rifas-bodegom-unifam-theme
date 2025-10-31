/**
 * Controller para crear nueva orden
 */

class NewOrderController {
    constructor(orderModel, raffleModel) {
        this.orderModel = orderModel;
        this.raffleModel = raffleModel;
    }

    data() {
        const orderModel = this.orderModel;
        const raffleModel = this.raffleModel;

        return {
            // Estado inicial
            orderInitialState: {
                raffle_id: '',
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                customer_address: '',
                ticket_quantity: 1,
                payment_installments: 1,
                notes: ''
            },

            // Datos del formulario
            order: {
                raffle_id: '',
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                customer_address: '',
                ticket_quantity: 1,
                payment_installments: 1,
                notes: ''
            },

            // Estados
            raffles: [],
            selectedRaffle: null,
            totalAmount: 0,
            loading: false,

            async init() {
                console.log("📝 NewOrder inicializado");
                this.resetForm();
                await this.loadRaffles();

                // Escuchar cambios de ruta
                window.addEventListener('route-changed', (e) => {
                    if (e.detail.view === 'order-form') {
                        console.log("🔄 Route changed - Reseteando formulario");
                        this.resetForm();
                        this.loadRaffles();
                    }
                });
            },

            // ✅ Resetear formulario
            resetForm() {
                this.order = {
                    raffle_id: '',
                    customer_name: '',
                    customer_email: '',
                    customer_phone: '',
                    customer_address: '',
                    ticket_quantity: 1,
                    payment_installments: 1,
                    notes: ''
                };
                this.selectedRaffle = null;
                this.totalAmount = 0;
                console.log("✅ Formulario de orden reseteado");
            },

            async loadRaffles() {
                try {
                    const allRaffles = await raffleModel.getAll();
                    this.raffles = allRaffles.filter(r => r.status === 'active');
                    console.log("✅ Rifas activas cargadas:", this.raffles.length);
                } catch (error) {
                    console.error("❌ Error cargando rifas:", error);
                    this.raffles = [];
                }
            },

            updateRaffleInfo() {
                if (!this.order.raffle_id) {
                    this.selectedRaffle = null;
                    this.totalAmount = 0;
                    return;
                }

                this.selectedRaffle = this.raffles.find(r => r.id == this.order.raffle_id);

                if (this.selectedRaffle && !this.selectedRaffle.allow_installments) {
                    this.order.payment_installments = 1;
                }

                this.calculateTotal();
                console.log("Rifa seleccionada:", this.selectedRaffle);
            },

            calculateTotal() {
                if (!this.selectedRaffle || !this.order.ticket_quantity) {
                    this.totalAmount = 0;
                    return;
                }

                this.totalAmount = this.selectedRaffle.price * this.order.ticket_quantity;
            },

            async submitOrder() {
                console.log("📤 Enviando orden:", this.order);

                // Validaciones
                if (!this.order.raffle_id) {
                    alert('Por favor selecciona una rifa');
                    return;
                }

                if (!this.order.customer_name || !this.order.customer_email || !this.order.customer_phone) {
                    alert('Por favor completa todos los campos obligatorios del cliente');
                    return;
                }

                if (this.order.ticket_quantity <= 0) {
                    alert('La cantidad de boletos debe ser mayor a 0');
                    return;
                }

                const available = this.selectedRaffle.ticket_limit - this.selectedRaffle.tickets_sold;
                if (this.order.ticket_quantity > available) {
                    alert(`Solo hay ${available} boletos disponibles`);
                    return;
                }

                this.loading = true;

                try {
                    const result = await orderModel.create({
                        raffle_id: this.order.raffle_id,
                        customer_name: this.order.customer_name,
                        customer_email: this.order.customer_email,
                        customer_phone: this.order.customer_phone,
                        customer_address: this.order.customer_address,
                        ticket_quantity: this.order.ticket_quantity,
                        payment_installments: this.order.payment_installments
                    });

                    console.log("✅ Resultado:", result);

                    if (result.success) {
                        alert(`Orden creada exitosamente\nNúmero de orden: ${result.order_number}`);
                        window.RaffleAppAdmin.router.navigate(`/order/view/${result.order_id}`);
                    } else {
                        alert('Error al crear orden: ' + result.message);
                    }
                } catch (error) {
                    console.error("❌ Error:", error);
                    alert('Error al crear la orden');
                }

                this.loading = false;
            },

            goBack() {
                window.RaffleAppAdmin.router.navigate('/dashboard');
            },

            // Helpers
            getStatusBadge(status) {
                const badges = {
                    'active': 'bg-green-100 text-green-800',
                    'paused': 'bg-yellow-100 text-yellow-800',
                    'completed': 'bg-blue-100 text-blue-800',
                    'cancelled': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            },

            getStatusText(status) {
                const texts = {
                    'active': 'Activa',
                    'paused': 'Pausada',
                    'completed': 'Completada',
                    'cancelled': 'Cancelada'
                };
                return texts[status] || status;
            }
        };
    }
}

export default NewOrderController;