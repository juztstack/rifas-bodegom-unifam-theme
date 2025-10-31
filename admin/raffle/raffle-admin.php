<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div 
    x-data="RaffleAppAdmin()" 
    x-cloak
    class="fixed inset-0 overflow-y-auto bg-gray-50 juzt-raffle-admin"
>
    <!-- TopBar -->
    <?php include get_template_directory() . '/admin/raffle/components/topbar.php'; ?>
    
    <!-- Contenedor Principal -->
    <main class="container px-4 py-8 pb-20 mx-auto">
        
        <!-- Vista: Dashboard -->
        <div x-show="currentView === 'dashboard'" x-data="RaffleAppDashboardView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/dashboard.php'; ?>
        </div>
        
        <!-- Vista: Detalle de Orden -->
        <div x-show="currentView === 'order-detail'" x-data="RaffleAppOrderView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/order-detail.php'; ?>
        </div>
        
        <!-- ✅ Vista: Nueva Orden -->
        <div x-show="currentView === 'order-form'" x-data="RaffleAppNewOrderView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/new-order.php'; ?>
        </div>

        <!-- Vista: Lista de Rifas -->
        <div x-show="isView('raffle-list')" x-data="RaffleAppRaffleListView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/raffle-list.php'; ?>
        </div>
        
        <!-- Vista: Formulario de Rifa -->
        <div x-show="isView('raffle-form')" x-data="RaffleAppRaffleFormView()" x-transition>
            <?php include get_template_directory() . '/admin/raffle/views/raffle-form.php'; ?>
        </div>
        
    </main>
</div>

<style>
    [x-cloak] { 
        display: none !important; 
    }
    
    .juzt-raffle-admin {
        height: 100vh;
        max-height: 100vh;
    }
</style>