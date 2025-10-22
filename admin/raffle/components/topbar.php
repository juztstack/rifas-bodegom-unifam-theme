<?php
/**
 * TopBar / Header de la aplicación admin
 */
?>

<header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="px-6 py-4">
        <div class="flex justify-between items-center">
            
            <!-- Menú Izquierdo -->
            <nav class="flex items-center space-x-4">
                <button 
                    @click="showView('dashboard')"
                    :class="isView('dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 font-medium rounded-lg transition-colors duration-200 cursor-pointer"
                >
                    <svg class="inline-block mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </button>
                
                <button 
                    @click="showView('raffles')"
                    :class="isView('raffle-list') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 font-medium rounded-lg transition-colors duration-200 cursor-pointer"
                >
                    <svg class="inline-block mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    Rifas
                </button>
                
                <button 
                    @click="showView('new-order')"
                    :class="isView('order-form') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:text-gray-900'"
                    class="px-4 py-2 font-medium rounded-lg transition-colors duration-200 cursor-pointer"
                >
                    <svg class="inline-block mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Orden
                </button>
            </nav>
            
            <!-- Título Centro -->
            <div class="absolute left-1/2 transform -translate-x-1/2">
                <h1 class="text-2xl font-bold tracking-tight text-gray-800">
                    🎟️ JUZT RAFFLE
                </h1>
            </div>
            
            <!-- Botón Salir Derecha -->
            <a 
                href="<?php echo admin_url(); ?>" 
                class="flex items-center px-4 py-2 font-medium text-red-600 rounded-lg transition-colors duration-200 cursor-pointer hover:bg-red-50"
            >
                <svg class="mr-2 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Salir
            </a>
            
        </div>
    </div>
</header>