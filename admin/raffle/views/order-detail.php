<?php
/**
 * Vista: Detalle de Orden
 */
?>

<div x-show="loading" class="flex items-center justify-center py-12">
    <svg class="w-8 h-8 text-blue-600 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
</div>

<div x-show="!loading && order" class="space-y-6">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <button 
                @click="goBack()"
                class="flex items-center mb-2 text-sm text-gray-600 hover:text-gray-900"
            >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver al dashboard
            </button>
            <h1 class="text-2xl font-bold text-gray-900">
                Orden <span x-text="order?.order_number"></span>
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                ID: #<span x-text="order?.id"></span>
            </p>
        </div>
        
        <div>
            <span 
                class="inline-flex px-4 py-2 text-sm font-semibold rounded-full"
                :class="getStatusBadge(order?.status)"
                x-text="getStatusText(order?.status)"
            ></span>
        </div>
    </div>
    
    <!-- Grid Principal -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Columna izquierda (2/3) -->
        <div class="space-y-6 lg:col-span-2">
            
            <!-- Información de la Rifa -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Información de la Rifa</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Rifa:</span>
                        <a 
                            :href="order?.raffle_permalink" 
                            target="_blank"
                            class="text-sm font-medium text-blue-600 hover:text-blue-800"
                            x-text="order?.raffle_title"
                        ></a>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Cantidad de boletos:</span>
                        <span class="text-sm font-medium text-gray-900" x-text="order?.ticket_quantity"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Precio por boleto:</span>
                        <span class="text-sm font-medium text-gray-900">
                            $<span x-text="(order?.total_amount / order?.ticket_quantity).toLocaleString()"></span>
                        </span>
                    </div>
                    <div class="pt-3 border-t">
                        <div class="flex justify-between">
                            <span class="font-medium text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-gray-900">
                                $<span x-text="parseFloat(order?.total_amount).toLocaleString()"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pagos / Cuotas -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Pagos</h2>
                <p class="text-sm text-gray-600">
                    Orden en <span x-text="order?.payment_installments"></span> cuota<span x-show="order?.payment_installments > 1">s</span>
                </p>
                
                <div class="mt-4 space-y-4">
                    <template x-for="payment in order?.payments" :key="payment.id">
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-medium text-gray-900">
                                            Cuota <span x-text="payment.installment_number"></span>
                                        </h3>
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getPaymentStatusBadge(payment.status)"
                                            x-text="getPaymentStatusText(payment.status)"
                                        ></span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Monto: <span class="font-medium">$<span x-text="parseFloat(payment.amount).toLocaleString()"></span></span>
                                    </p>
                                    
                                    <!-- Comprobante -->
                                    <div x-show="payment.payment_proof_url" class="mt-3">
                                        <p class="text-xs text-gray-500">Comprobante:</p>
                                        <a 
                                            :href="payment.payment_proof_url" 
                                            target="_blank"
                                            class="inline-block mt-1"
                                        >
                                            <img 
                                                :src="payment.payment_proof_url" 
                                                class="object-cover w-32 h-32 border rounded"
                                                alt="Comprobante"
                                            >
                                        </a>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Subido: <span x-text="formatDate(payment.uploaded_at)"></span>
                                        </p>
                                    </div>
                                    
                                    <!-- Verificado por -->
                                    <div x-show="payment.verified_at" class="mt-2">
                                        <p class="text-xs text-gray-500">
                                            Verificado: <span x-text="formatDate(payment.verified_at)"></span>
                                        </p>
                                        <p x-show="payment.notes" class="mt-1 text-xs italic text-gray-600">
                                            Notas: <span x-text="payment.notes"></span>
                                        </p>
                                    </div>
                                    
                                    <!-- Rechazado -->
                                    <div x-show="payment.status === 'rejected' && payment.rejection_reason" class="mt-2">
                                        <p class="text-xs font-medium text-red-600">
                                            Motivo de rechazo:
                                        </p>
                                        <p class="mt-1 text-xs text-red-600" x-text="payment.rejection_reason"></p>
                                    </div>
                                </div>
                                
                                <!-- Acciones -->
                                <div x-show="payment.status === 'pending' && payment.payment_proof_url" class="ml-4 space-y-2">
                                    <button 
                                        @click="verifyPayment(payment.installment_number)"
                                        :disabled="processing"
                                        class="px-3 py-1 text-xs text-white transition-colors bg-green-600 rounded hover:bg-green-700 disabled:opacity-50"
                                    >
                                        Verificar
                                    </button>
                                    <button 
                                        @click="rejectPayment(payment.installment_number)"
                                        :disabled="processing"
                                        class="px-3 py-1 text-xs text-white transition-colors bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
                                    >
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            <!-- Números Asignados -->
            <div x-show="order?.numbers && order.numbers.length > 0" class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Números Asignados</h2>
                <div class="flex flex-wrap gap-2 mt-4">
                    <template x-for="number in order?.numbers" :key="number">
                        <span class="px-3 py-1 font-mono text-sm font-medium text-blue-900 bg-blue-100 rounded">
                            <span x-text="number"></span>
                        </span>
                    </template>
                </div>
            </div>
            
            <!-- Timeline / Historial -->
            <div x-show="order?.history && order.history.length > 0" class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Historial</h2>
                <div class="mt-4 space-y-4">
                    <template x-for="event in order?.history" :key="event.id">
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 bg-gray-200 rounded-full">
                                    <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900" x-text="event.description"></div>
                                <div class="mt-1 text-xs text-gray-500">
                                    <span x-text="formatDate(event.created_at)"></span>
                                    <span x-show="event.created_by !== 'system'" class="ml-2">
                                        por <span x-text="event.created_by"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
        </div>
        
        <!-- Columna derecha (1/3) -->
        <div class="space-y-6">
            
            <!-- Información del Cliente -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Cliente</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Nombre</p>
                        <p class="text-sm font-medium text-gray-900" x-text="order?.customer_name"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <a 
                            :href="'mailto:' + order?.customer_email" 
                            class="text-sm text-blue-600 hover:text-blue-800"
                            x-text="order?.customer_email"
                        ></a>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Teléfono</p>
                        <a 
                            :href="'tel:' + order?.customer_phone" 
                            class="text-sm text-blue-600 hover:text-blue-800"
                            x-text="order?.customer_phone"
                        ></a>
                    </div>
                    <div x-show="order?.customer_address">
                        <p class="text-xs text-gray-500">Dirección</p>
                        <p class="text-sm text-gray-900" x-text="order?.customer_address"></p>
                    </div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Acciones</h2>
                <div class="mt-4 space-y-3">
                    
                    <!-- Aprobar Orden -->
                    <button 
                        x-show="order?.status === 'payment_complete'"
                        @click="approveOrder()"
                        :disabled="processing"
                        class="w-full px-4 py-2 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50"
                    >
                        <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Aprobar y Asignar Números
                    </button>
                    
                    <!-- Rechazar Orden -->
                    <button 
                        x-show="order?.status === 'pending' || order?.status === 'payment_complete'"
                        @click="rejectOrder()"
                        :disabled="processing"
                        class="w-full px-4 py-2 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
                    >
                        <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Rechazar Orden
                    </button>
                    
                    <!-- Estado completado -->
                    <div x-show="order?.status === 'completed'" class="p-4 text-green-800 bg-green-100 rounded-lg">
                        <p class="text-sm font-medium">✅ Orden completada</p>
                        <p class="mt-1 text-xs">Números asignados exitosamente</p>
                    </div>
                    
                    <!-- Estado rechazado -->
                    <div x-show="order?.status === 'rejected'" class="p-4 text-red-800 bg-red-100 rounded-lg">
                        <p class="text-sm font-medium">❌ Orden rechazada</p>
                        <p x-show="order?.rejection_reason" class="mt-2 text-xs">
                            Motivo: <span x-text="order?.rejection_reason"></span>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Información</h2>
                <div class="mt-4 space-y-2 text-xs text-gray-600">
                    <p>
                        Creada: <span class="font-medium" x-text="formatDate(order?.created_at)"></span>
                    </p>
                    <p x-show="order?.updated_at">
                        Actualizada: <span class="font-medium" x-text="formatDate(order?.updated_at)"></span>
                    </p>
                    <p x-show="order?.approved_at">
                        Aprobada: <span class="font-medium" x-text="formatDate(order?.approved_at)"></span>
                    </p>
                    <p x-show="order?.approved_by_name">
                        Aprobada por: <span class="font-medium" x-text="order?.approved_by_name"></span>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
    
</div>

<!-- Empty/Error state -->
<div x-show="!loading && !order" class="p-12 text-center">
    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">Orden no encontrada</h3>
    <button 
        @click="goBack()"
        class="px-4 py-2 mt-4 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
    >
        Volver al dashboard
    </button>
</div>