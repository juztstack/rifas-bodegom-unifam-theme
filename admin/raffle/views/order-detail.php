<?php
/**
 * Vista: Detalle de Orden
 */
echo "hola";
?>

<div x-data="{ order: getOrder(selectedOrderId) }">
    <!-- Botón volver -->
    <button 
        @click="showView('dashboard')"
        class="flex items-center mb-4 text-gray-600 transition-colors hover:text-gray-900"
    >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver al dashboard
    </button>
    
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Detalle de Orden</h2>
                    <p class="mt-1 text-sm text-gray-600" x-text="order?.order_number || 'Cargando...'"></p>
                </div>
                
                <span 
                    class="inline-flex px-4 py-2 text-sm font-semibold leading-5 rounded-full"
                    :class="getStatusBadge(order?.status)"
                    x-text="getStatusText(order?.status)"
                ></span>
            </div>
        </div>
        
        <div class="px-6 py-6">
            <template x-if="order">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    
                    <!-- Información del cliente -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Información del Cliente</h3>
                        <div class="p-4 space-y-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Nombre</label>
                                <p class="text-sm text-gray-900" x-text="order.customer_name"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Email</label>
                                <p class="text-sm text-gray-900" x-text="order.customer_email"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de la compra -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900">Información de Compra</h3>
                        <div class="p-4 space-y-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Rifa</label>
                                <p class="text-sm text-gray-900" x-text="order.raffle_title"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Boletos</label>
                                <p class="text-sm text-gray-900" x-text="order.ticket_quantity"></p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Total</label>
                                <p class="text-lg font-semibold text-gray-900">$<span x-text="order.total_amount.toLocaleString()"></span></p>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </template>
            
            <!-- Acciones -->
            <div class="flex mt-8 space-x-4">
                <button 
                    x-show="order?.status === 'pending'"
                    class="px-6 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700"
                >
                    Aprobar Orden
                </button>
                <button 
                    x-show="order?.status === 'pending'"
                    class="px-6 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700"
                >
                    Rechazar Orden
                </button>
            </div>
        </div>
    </div>
</div>