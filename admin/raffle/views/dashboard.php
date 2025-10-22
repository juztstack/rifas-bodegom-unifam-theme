<?php
/**
 * Vista: Dashboard - Lista de órdenes
 */

?>

<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Órdenes de Rifas</h2>
                <p class="mt-1 text-sm text-gray-600">Administra todas las órdenes del sistema</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <input placeholder="Buscar" type="search" class="px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <!-- Filtro por estado -->
                <select class="px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="approved">Aprobadas</option>
                    <option value="completed">Completadas</option>
                    <option value="rejected">Rechazadas</option>
                </select>
                
                <!-- Botón refrescar -->
                <button 
                    @click="loadOrders()"
                    class="px-4 py-2 text-white bg-blue-600 rounded-lg transition-colors hover:bg-blue-700"
                >
                    <svg class="inline-block w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading -->
    <div x-show="loading" class="px-6 py-12 text-center">
        <svg class="mx-auto w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">Cargando órdenes...</p>
    </div>
    
    <!-- Tabla de órdenes -->
    <div x-show="!loading" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Orden</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cliente</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rifa</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Boletos</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="order in orders" :key="order.id">
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900" x-text="order.order_number"></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <div class="font-medium text-gray-900" x-text="order.customer_name"></div>
                                <div class="text-gray-500" x-text="order.customer_email"></div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900" x-text="order.raffle_title"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            <span x-text="order.ticket_quantity"></span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            $<span x-text="order.total_amount.toLocaleString()"></span>
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
                            >
                                Ver detalle
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Empty state -->
        <div x-show="orders.length === 0" class="px-6 py-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay órdenes</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva orden</p>
        </div>
    </div>
</div>