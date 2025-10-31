<?php
/**
 * Vista: Nueva Orden Manual
 */
?>

<div class="max-w-4xl mx-auto">
    
    <!-- Header -->
    <div class="mb-6">
        <button 
            @click="goBack()"
            class="flex items-center mb-2 text-sm text-gray-600 hover:text-gray-900"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Volver al dashboard
        </button>
        <h1 class="text-2xl font-bold text-gray-900">Nueva Orden</h1>
        <p class="mt-1 text-sm text-gray-600">Crea una orden manualmente para un cliente</p>
    </div>
    
    <!-- Formulario -->
    <form @submit.prevent="submitOrder()" class="p-6 bg-white rounded-lg shadow-sm">
        
        <!-- Sección: Seleccionar Rifa -->
        <div class="pb-6 mb-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Seleccionar Rifa</h2>
            <p class="text-sm text-gray-600">Elige la rifa para esta orden</p>
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">
                    Rifa <span class="text-red-500">*</span>
                </label>
                <select 
                    x-model="order.raffle_id"
                    @change="updateRaffleInfo()"
                    required
                    class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">Selecciona una rifa</option>
                    <template x-for="raffle in raffles" :key="raffle.id">
                        <option :value="raffle.id" x-text="raffle.title + ' - $' + raffle.price.toLocaleString()"></option>
                    </template>
                </select>
            </div>
            
            <!-- Info de la rifa seleccionada -->
            <div x-show="selectedRaffle" class="p-4 mt-4 border border-blue-200 rounded-lg bg-blue-50">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Precio por boleto:</span>
                        <span class="ml-2 font-medium text-gray-900">
                            $<span x-text="selectedRaffle?.price.toLocaleString()"></span>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Disponibles:</span>
                        <span class="ml-2 font-medium text-gray-900">
                            <span x-text="(selectedRaffle?.ticket_limit - selectedRaffle?.tickets_sold).toLocaleString()"></span> boletos
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">Permite cuotas:</span>
                        <span class="ml-2 font-medium text-gray-900" x-text="selectedRaffle?.allow_installments ? 'Sí' : 'No'"></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Estado:</span>
                        <span 
                            class="inline-flex px-2 py-1 ml-2 text-xs font-semibold rounded-full"
                            :class="getStatusBadge(selectedRaffle?.status)"
                            x-text="getStatusText(selectedRaffle?.status)"
                        ></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección: Datos del Cliente -->
        <div class="pb-6 mb-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Datos del Cliente</h2>
            <p class="text-sm text-gray-600">Información de contacto del comprador</p>
            
            <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        x-model="order.customer_name"
                        required
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ej: Juan Pérez"
                    >
                </div>
                
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        x-model="order.customer_email"
                        required
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="ejemplo@correo.com"
                    >
                </div>
                
                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Teléfono <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="tel" 
                        x-model="order.customer_phone"
                        required
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="300 123 4567"
                    >
                </div>
                
                <!-- Dirección -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Dirección (Opcional)
                    </label>
                    <input 
                        type="text" 
                        x-model="order.customer_address"
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Calle 123 #45-67"
                    >
                </div>
            </div>
        </div>
        
        <!-- Sección: Detalles de la Compra -->
        <div class="pb-6 mb-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Detalles de la Compra</h2>
            <p class="text-sm text-gray-600">Cantidad de boletos y forma de pago</p>
            
            <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                <!-- Cantidad de boletos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Cantidad de Boletos <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        x-model.number="order.ticket_quantity"
                        @input="calculateTotal()"
                        min="1"
                        :max="selectedRaffle ? (selectedRaffle.ticket_limit - selectedRaffle.tickets_sold) : 1"
                        required
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ej: 5"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        Máximo disponible: <span x-text="selectedRaffle ? (selectedRaffle.ticket_limit - selectedRaffle.tickets_sold) : 0"></span>
                    </p>
                </div>
                
                <!-- Cuotas -->
                <div x-show="selectedRaffle?.allow_installments">
                    <label class="block text-sm font-medium text-gray-700">
                        Forma de Pago
                    </label>
                    <select 
                        x-model.number="order.payment_installments"
                        @change="calculateTotal()"
                        class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option :value="1">Pago de contado</option>
                        <option :value="2">2 cuotas</option>
                        <option :value="3">3 cuotas</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Resumen -->
        <div x-show="order.raffle_id && order.ticket_quantity > 0" class="p-6 mb-6 border-2 border-blue-200 rounded-lg bg-blue-50">
            <h3 class="text-lg font-semibold text-gray-900">Resumen de la Orden</h3>
            
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Precio por boleto:</span>
                    <span class="font-medium text-gray-900">$<span x-text="selectedRaffle?.price.toLocaleString()"></span></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Cantidad:</span>
                    <span class="font-medium text-gray-900"><span x-text="order.ticket_quantity"></span> boleto<span x-show="order.ticket_quantity > 1">s</span></span>
                </div>
                <div x-show="order.payment_installments > 1" class="flex justify-between text-sm">
                    <span class="text-gray-600">Forma de pago:</span>
                    <span class="font-medium text-gray-900"><span x-text="order.payment_installments"></span> cuotas</span>
                </div>
                <div x-show="order.payment_installments > 1" class="flex justify-between text-sm">
                    <span class="text-gray-600">Valor por cuota:</span>
                    <span class="font-medium text-gray-900">$<span x-text="(totalAmount / order.payment_installments).toLocaleString()"></span></span>
                </div>
                <div class="pt-2 mt-2 border-t border-blue-300">
                    <div class="flex justify-between">
                        <span class="text-lg font-semibold text-gray-900">Total:</span>
                        <span class="text-2xl font-bold text-blue-900">$<span x-text="totalAmount.toLocaleString()"></span></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notas adicionales -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700">
                Notas Internas (Opcional)
            </label>
            <textarea 
                x-model="order.notes"
                rows="3"
                class="block w-full px-4 py-2 mt-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Notas adicionales sobre esta orden..."
            ></textarea>
            <p class="mt-1 text-xs text-gray-500">Estas notas son solo para uso interno del admin</p>
        </div>
        
        <!-- Botones de acción -->
        <div class="flex justify-end space-x-4">
            <button 
                type="button"
                @click="goBack()"
                :disabled="loading"
                class="px-6 py-2 text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
            >
                Cancelar
            </button>
            <button 
                type="submit"
                :disabled="loading || !order.raffle_id || !order.customer_name || !order.customer_email || !order.customer_phone || order.ticket_quantity <= 0"
                class="px-6 py-2 text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg 
                    x-show="loading" 
                    class="inline-block w-4 h-4 mr-2 animate-spin" 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-show="!loading">Crear Orden</span>
                <span x-show="loading">Creando...</span>
            </button>
        </div>
        
    </form>
    
</div>