/**
 * Controller para gestión de órdenes
 */

class OrderController {
    constructor(orderModel) {
        this.orderModel = orderModel;
    }

    data() {
        const orderModel = this.orderModel;

        return {
            order: null,
            loading: false,
            processing: false,
            editMode: false,
            newComment: '',
            editData: {
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                customer_address: ''
            },

            init() {
                console.log("📄 OrderController inicializado");
                this.checkAndLoadOrder(); // ✅ FALTABA ESTA LÍNEA

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

            // ✅ Activar modo edición
            enableEdit() {
                this.editMode = true;
                this.editData = {
                    customer_name: this.order.customer_name,
                    customer_email: this.order.customer_email,
                    customer_phone: this.order.customer_phone,
                    customer_address: this.order.customer_address || ''
                };
            },

            // ✅ Cancelar edición
            cancelEdit() {
                this.editMode = false;
            },

            // ✅ Guardar cambios
            async saveEdit() {
                if (!this.editData.customer_name || !this.editData.customer_email || !this.editData.customer_phone) {
                    alert('Por favor completa todos los campos obligatorios');
                    return;
                }

                this.processing = true;
                try {
                    const result = await orderModel.update(this.order.id, this.editData);

                    if (result.success) {
                        alert('Orden actualizada exitosamente');
                        this.editMode = false;
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al actualizar orden');
                }
                this.processing = false;
            },

            // ✅ Eliminar orden
            async deleteOrder() {
                if (!confirm('¿Estás seguro de eliminar esta orden? Esta acción no se puede deshacer.')) {
                    return;
                }

                this.processing = true;
                try {
                    const result = await orderModel.delete(this.order.id);

                    if (result.success) {
                        alert('Orden eliminada exitosamente');
                        window.RaffleAppAdmin.router.navigate('/dashboard');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al eliminar orden');
                }
                this.processing = false;
            },

            // ✅ Cambiar estado manualmente
            async changeStatus() {
                // Definir estados disponibles según el estado actual
                const availableStatuses = {
                    'pending': ['payment_complete', 'rejected'],
                    'payment_complete': ['pending', 'completed', 'rejected'],
                    'approved': ['completed', 'rejected'],
                    'completed': ['pending'],
                    'rejected': ['pending']
                };

                const options = availableStatuses[this.order.status] || [];

                if (options.length === 0) {
                    alert('No hay estados disponibles para cambiar');
                    return;
                }

                // Crear opciones para el prompt
                const statusLabels = {
                    'pending': 'Pendiente',
                    'payment_complete': 'Pagos Completos',
                    'approved': 'Aprobada',
                    'completed': 'Completada',
                    'rejected': 'Rechazada'
                };

                let message = 'Selecciona el nuevo estado:\n\n';
                options.forEach((status, index) => {
                    message += `${index + 1}. ${statusLabels[status]}\n`;
                });

                const choice = prompt(message + '\nIngresa el número:');

                if (!choice) return;

                const index = parseInt(choice) - 1;
                if (index < 0 || index >= options.length) {
                    alert('Opción inválida');
                    return;
                }

                const newStatus = options[index];
                const notes = prompt('Notas sobre el cambio de estado (opcional):');

                if (notes === null) return; // User cancelled

                this.processing = true;
                try {
                    const result = await orderModel.changeStatus(this.order.id, newStatus, notes);

                    if (result.success) {
                        alert('Estado actualizado exitosamente');
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al cambiar estado');
                }
                this.processing = false;
            },

            async addComment() {
                if (!this.newComment || this.newComment.trim() === '') {
                    return;
                }

                this.processing = true;
                try {
                    const result = await orderModel.addComment(this.order.id, this.newComment);

                    if (result.success) {
                        alert('Comentario agregado exitosamente');
                        this.newComment = ''; // ✅ Limpiar textarea
                        await this.loadOrder(this.order.id);
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert('Error al agregar comentario');
                }
                this.processing = false;
            },

            // ✅ Subir comprobante manualmente (admin)
            async uploadProofAdmin(installmentNumber) {
                // Crear input file temporal
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*,application/pdf';

                input.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    // Validar tamaño
                    if (file.size > 5 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 5MB');
                        return;
                    }

                    this.processing = true;

                    try {
                        const result = await orderModel.uploadPaymentProofAdmin(
                            this.order.id,
                            installmentNumber,
                            file
                        );

                        if (result.success) {
                            alert('Comprobante subido exitosamente');
                            await this.loadOrder(this.order.id);
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (error) {
                        console.error("Error:", error);
                        alert('Error al subir comprobante');
                    }

                    this.processing = false;
                };

                input.click();
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