import Alpine from 'alpinejs';
import RaffleAdminApp from './modules/raffles/app.js';

// Instanciar la aplicación (SIN router todavía)
const RaffleApp = new RaffleAdminApp();

// Exponer al window
window.RaffleAppAdmin = RaffleApp;

// ============================================
// ✅ AHORA SÍ - INICIALIZAR ROUTER
// ============================================

RaffleApp.initRouter(); // Crear el router

// ✅ REGISTRAR RUTAS (ahora el router ya existe)
RaffleApp.router.register('/dashboard', 'dashboard');
RaffleApp.router.register('/orders', 'dashboard'); // Alias

RaffleApp.router.register('/order/new', 'order-form');
RaffleApp.router.register('/order/view/:id', 'order-detail');
RaffleApp.router.register('/order/:id', 'order-detail'); // Alias
RaffleApp.router.register('/new-order', 'order-form'); // Alias

RaffleApp.router.register('/raffles', 'raffle-list');
RaffleApp.router.register('/raffle/new', 'raffle-form');
RaffleApp.router.register('/raffle/edit/:id', 'raffle-form');

// ============================================
// CONFIGURAR ALPINE
// ============================================

window.addEventListener("alpine:init", () => {
    
    Alpine.data("RaffleAppAdmin", () => ({
        currentView: 'dashboard',
        routerParams: {},
        
        init() {
            console.log("🎟️ Juzt Raffle Admin inicializado");

            window.addEventListener("route-changed", (e) => {
                this.currentView = e.detail.view;
                this.routerParams = e.detail.params;
                console.log("📍 Vista cambiada:", e.detail.view, e.detail.params);
            });

            this.currentView = RaffleApp.router.currentRoute || "dashboard";
            this.routerParams = RaffleApp.router.params;
        },

        isView(name) {
            return this.currentView === name;
        },
        
        showView(view) {
            RaffleApp.router.navigate(`/${view}`);
        }
    }));

    Alpine.data("RaffleAppDashboardView", () => RaffleApp.controllers.dashboard.data());
    Alpine.data("RaffleAppOrderView", () => RaffleApp.controllers.order.data());

    Alpine.data("RaffleAppRaffleListView", () => RaffleApp.controllers.raffle.listData());
    Alpine.data("RaffleAppRaffleFormView", () => RaffleApp.controllers.raffle.formData());
    Alpine.data("RaffleAppNewOrderView", () => RaffleApp.controllers.newOrder.data());

    console.log("✅ Alpine configurado correctamente");
});

window.Alpine = Alpine;
Alpine.start();