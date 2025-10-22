class Store {
    constructor() {
        this.state = {
            orders: [],
            raffles: [],
            loading: false,
            user: null
        };
        
        this.listeners = [];
    }
    
    /**
     * Obtener estado
     */
    getState() {
        return this.state;
    }
    
    /**
     * Actualizar estado
     */
    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.notify();
    }
    
    /**
     * Suscribirse a cambios
     */
    subscribe(listener) {
        this.listeners.push(listener);
    }
    
    /**
     * Notificar a listeners
     */
    notify() {
        this.listeners.forEach(listener => listener(this.state));
    }
}

export default Store;