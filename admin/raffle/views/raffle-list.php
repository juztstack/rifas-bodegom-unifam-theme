<?php
/**
 * Vista: Lista de Rifas
 */
?>

<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Rifas</h2>
                <p class="mt-1 text-sm text-gray-600">Administra todas las rifas del sistema</p>
            </div>
            
            <button 
                @click="createNew()"
                class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg transition-colors hover:bg-blue-700"
            >
                <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Rifa
            </button>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center space-x-4">
            <!-- Búsqueda -->
            <div class="flex-1">
                <input 
                    type="text" 
                    x-model="search"
                    placeholder="Buscar rifas..."
                    class="px-4 py-2 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
            
            <!-- Filtro por estado -->
            <select 
                x-model="statusFilter"
                class="px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Todos los estados</option>
                <option value="active">Activas</option>
                <option value="paused">Pausadas</option>
                <option value="completed">Completadas</option>
                <option value="cancelled">Canceladas</option>
            </select>
            
            <!-- Botón refrescar -->
            <button 
                @click="loadRaffles()"
                class="px-4 py-2 text-white bg-blue-600 rounded-lg transition-colors hover:bg-blue-700"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Loading -->
    <div x-show="loading" class="px-6 py-12 text-center">
        <svg class="mx-auto w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-gray-600">Cargando rifas...</p>
    </div>
    
    <!-- Tabla de rifas -->
    <div x-show="!loading" class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rifa</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Precio</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Boletos</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Vendidos</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="raffle in filteredRaffles" :key="raffle.id">
                    <tr class="transition-colors hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img 
                                    x-show="raffle.featured_image" 
                                    :src="raffle.featured_image" 
                                    class="mr-3 w-10 h-10 rounded"
                                    :alt="raffle.title"
                                >
                                <div>
                                    <div class="text-sm font-medium text-gray-900" x-text="raffle.title"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            $<span x-text="raffle.price?.toLocaleString()"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            <span x-text="raffle.ticket_limit?.toLocaleString()"></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                            <span x-text="raffle.tickets_sold || 0"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span 
                                class="inline-flex px-3 py-1 text-xs font-semibold leading-5 rounded-full"
                                :class="getStatusBadge(raffle.status)"
                                x-text="getStatusText(raffle.status)"
                            ></span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            <span x-text="new Date(raffle.created_at).toLocaleDateString('es-ES')"></span>
                        </td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            <div class="flex space-x-2">
                                <a 
                                    :href="getRaffleUrl(raffle.id)"
                                    target="_blank"
                                    class="font-medium text-green-600 hover:text-green-900"
                                    title="Ver rifa en el sitio"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <button 
                                    @click="editRaffle(raffle.id)"
                                    class="font-medium text-blue-600 hover:text-blue-900"
                                >
                                    Editar
                                </button>
                                <button 
                                    @click="deleteRaffle(raffle.id, raffle.title)"
                                    class="font-medium text-red-600 hover:text-red-900"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        
        <!-- Empty state -->
        <div x-show="filteredRaffles.length === 0" class="px-6 py-12 text-center">
            <svg class="mx-auto w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay rifas</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva rifa</p>
            <button 
                @click="createNew()"
                class="px-4 py-2 mt-4 text-white bg-blue-600 rounded-lg transition-colors hover:bg-blue-700"
            >
                Crear Rifa
            </button>
        </div>
    </div>
</div>