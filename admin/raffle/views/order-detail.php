<?php
/**
 * Vista: Detalle de Orden
 */
?>

<!-- ⚠️ NO más x-data inline aquí - el componente padre ya tiene los datos -->
<div>
    <!-- Botón volver -->
    <button 
        @click="goBack()"
        class="flex items-center mb-4 text-gray-600 transition-colors hover:text-gray-900"
    >
        <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver al dashboard
    </button>
    
    <!-- Loading -->
    <div x-show="loading" class="px-6 py-12 text-center">
        <svg class="mx-auto w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">Cargando orden...</p>
    </div>
    
    <!-- Contenido -->
    <div x-show="!loading && order" class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                
                <!-- Información del cliente -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Información del Cliente</h3>
                    <div class="p-4 space-y-3 bg-gray-50 rounded-lg">
                        <div>
                            <label class="text-xs font-medium text-gray-500">Nombre</label>
                            <p class="text-sm text-gray-900" x-text="order?.customer_name"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Email</label>
                            <p class="text-sm text-gray-900" x-text="order?.customer_email"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la compra -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900">Información de Compra</h3>
                    <div class="p-4 space-y-3 bg-gray-50 rounded-lg">
                        <div>
                            <label class="text-xs font-medium text-gray-500">Rifa</label>
                            <p class="text-sm text-gray-900" x-text="order?.raffle_title"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Boletos</label>
                            <p class="text-sm text-gray-900" x-text="order?.ticket_quantity"></p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Total</label>
                            <p class="text-lg font-semibold text-gray-900">$<span x-text="order?.total_amount?.toLocaleString()"></span></p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Acciones -->
            <div class="flex mt-8 space-x-4">
                <button 
                    x-show="order?.status === 'pending'"
                    @click="approveOrder()"
                    class="px-6 py-2 text-white bg-green-600 rounded-lg transition-colors hover:bg-green-700"
                >
                    Aprobar Orden
                </button>
                <button 
                    x-show="order?.status === 'pending'"
                    @click="rejectOrder()"
                    class="px-6 py-2 text-white bg-red-600 rounded-lg transition-colors hover:bg-red-700"
                >
                    Rechazar Orden
                </button>
            </div>
        </div>
    </div>
    
    <!-- Error state -->
    <div x-show="!loading && !order" class="px-6 py-12 text-center bg-white rounded-lg shadow-sm">
        <p class="text-gray-600">No se pudo cargar la orden</p>
        <button 
            @click="goBack()"
            class="px-4 py-2 mt-4 text-white bg-blue-600 rounded-lg transition-colors hover:bg-blue-700"
        >
            Volver al Dashboard
        </button>
    </div>
</div>