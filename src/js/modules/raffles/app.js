import AppRouter from './router.js';
import Store from './store.js';
import OrderController from './controller/order.js';
import DashboardController from './controller/dashboard.js';
import OrderModel from './model/order.js';
import RaffleModel from './model/raffle.js';

class RaffleAdminApp {
    constructor() {
        // Inicializar Store
        this.store = new Store();
        
        // ⚠️ NO inicializar Router aquí todavía
        this.router = null;
        
        // Inicializar Models
        this.models = {
            order: new OrderModel(),
            raffle: new RaffleModel(),
        };
        
        // Inicializar Controllers
        this.controllers = {
            dashboard: new DashboardController(this.models.order),
            order: new OrderController(this.models.order),
        };
        
        console.log('📦 RaffleAdminApp instanciada (router pendiente)');
    }
    
    // ✅ Método para inicializar router DESPUÉS de registrar rutas
    initRouter() {
        this.router = new AppRouter();
        console.log('🚦 Router inicializado');
    }
}

export default RaffleAdminApp;