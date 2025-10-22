<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!--<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>-->

<div 
    x-data="RaffleAppAdmin()" 
    x-cloak
    class="fixed top-0 left-0 w-full min-h-screen bg-gray-50 juzt-raffle-admin scroll-auto"
>
    <!-- TopBar -->
    <?php include get_template_directory() . '/admin/raffle/components/topbar.php'; ?>
    
    <!-- Contenedor Principal -->
    <main class="container px-4 py-8 mx-auto">
        
        <!-- Vista: Dashboard -->
        <div x-show="currentView === 'dashboard'" x-data="RaffleAppDashboardView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/dashboard.php'; ?>
        </div>
        
        <!-- Vista: Detalle de Orden -->
        <div x-show="currentView === 'order'" x-data="RaffleAppOrderView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/order-detail.php'; ?>
        </div>
        
        <!-- Vista: Nueva Orden -->
        <div x-show="currentView === 'new-order'" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/new-order.php'; ?>
        </div>
        
    </main>
</div>

<style>
    [x-cloak] { 
        display: none !important; 
    }
</style>