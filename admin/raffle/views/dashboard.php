<?php
/**
 * Vista: Dashboard - Lista de Órdenes
 */
?>

<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Órdenes</h2>
                <p class="mt-1 text-sm text-gray-600">Gestiona las órdenes de compra</p>
            </div>
            
            <button 
                @click="window.RaffleAppAdmin.router.navigate('/order/new')"
                class="flex items-center px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Orden
            </button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center space-x-4">
            <!-- Búsqueda -->
            <div class="flex-1">
                <input 
                    type="text" 
                    x-model="filters.search"
                    @input="loadOrders()"
                    placeholder="Buscar por número de orden, cliente..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <!-- Filtro por estado -->
            <select 
                x-model="filters.status"
                @change="loadOrders()"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Todos los estados</option>
                <option value="pending">Pendientes</option>
                <option value="payment_complete">Pagos Completos</option>
                <option value="approved">Aprobadas</option>
                <option value="completed">Completadas</option>
                <option value="rejected">Rechazadas</option>
            </select>
            
            <!-- Botón refrescar -->
            <button 
                @click="loadOrders()"
                :disabled="loading"
                class="px-4 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-4">
        <div class="p-4 border border-gray-200 rounded-lg">
            <div class="text-sm text-gray-600">Total Órdenes</div>
            <div class="mt-1 text-2xl font-bold text-gray-900" x-text="orders.length"></div>
        </div>
        <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50">
            <div class="text-sm text-yellow-800">Pendientes</div>
            <div class="mt-1 text-2xl font-bold text-yellow-900" x-text="getOrdersByStatus('pending').length"></div>
        </div>
        <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
            <div class="text-sm text-blue-800">Pagos Completos</div>
            <div class="mt-1 text-2xl font-bold text-blue-900" x-text="getOrdersByStatus('payment_complete').length"></div>
        </div>
        <div class="p-4 border border-green-200 rounded-lg bg-green-50">
            <div class="text-sm text-green-800">Completadas</div>
            <div class="mt-1 text-2xl font-bold text-green-900" x-text="getOrdersByStatus('completed').length"></div>
        </div>
    </div>
    
    <!-- Loading -->
    <div x-show="loading" class="px-6 py-12 text-center">
        <svg class="w-8 h-8 mx-auto text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">Cargando órdenes...</p>
    </div>
    
    <!-- Tabla de órdenes -->
    <div x-show="!loading" class="overflow-x-auto">
        <table class="w-full">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Orden</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rifa</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Boletos</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cuotas</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="order in filteredOrders" :key="order.id">
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900" x-text="order.order_number"></div>
                            <div class="text-xs text-gray-500">#<span x-text="order.id"></span></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900" x-text="order.customer_name"></div>
                            <div class="text-xs text-gray-500" x-text="order.customer_email"></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900" x-text="order.raffle_title"></div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            <span x-text="order.ticket_quantity"></span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            $<span x-text="parseFloat(order.total_amount).toLocaleString()"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            <span x-text="order.payment_installments"></span> cuota<span x-show="order.payment_installments > 1">s</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span 
                                class="inline-flex px-3 py-1 text-xs font-semibold leading-5 rounded-full"
                                :class="getStatusBadge(order.status)"
                                x-text="getStatusText(order.status)"
                            ></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            <span x-text="new Date(order.created_at).toLocaleDateString('es-ES')"></span>
                        </td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            <button 
                                @click="viewOrder(order.id)"
                                class="font-medium text-blue-600 hover:text-blue-900"
                                title="Ver detalles"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Empty state -->
        <div x-show="filteredOrders.length === 0" class="px-6 py-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay órdenes</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva orden</p>
        </div>
    </div>
</div>