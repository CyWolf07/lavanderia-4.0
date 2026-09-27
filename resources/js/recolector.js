export default function recolectorForm({ clientes, prendas, fechaIngreso, clienteInicial, oldItems, puedeEditarPrecios, numeroFactura, facturas, ordenPrintBase }) {
    // modalEstatus y modalOrdenes se agregan vía x-data en la plantilla blade
    return {
        clientes,
        prendas,
        facturas,
        fechaIngreso,
        puedeEditarPrecios,
        numeroFactura,
        clienteId: clienteInicial ? String(clienteInicial) : '',
        clienteActual: {},
        buscarCliente: '',
        buscarPrenda: '',
        guardando: false,
        erroresFormulario: [],
        campoError: null,
        mostrarErrores(errores) {
            if (this.campoError) {
                this.campoError.removeAttribute('aria-invalid');
                this.campoError.classList.remove('ring-2', 'ring-rose-500');
            }
            this.erroresFormulario = errores;
            this.campoError = errores[0]?.campo || null;
            if (this.campoError) {
                this.campoError.setAttribute('aria-invalid', 'true');
                this.campoError.classList.add('ring-2', 'ring-rose-500');
                this.campoError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                this.campoError.focus({ preventScroll: true });
            }
        },
        validarCampos(form) {
            return Array.from(form.elements).filter(campo => campo.willValidate && !campo.validity.valid).map(campo => {
                const nombre = campo.dataset.validationLabel || campo.getAttribute('aria-label') || campo.name || 'Dato';
                return { campo, mensaje: campo.validity.valueMissing ? `Falta completar: ${nombre}.` : `${nombre}: ${campo.validationMessage}` };
            });
        },
        guardarCliente(event) {
            const errores = this.validarCampos(event.target);
            this.mostrarErrores(errores);
            if (errores.length) event.preventDefault();
        },
        online: navigator.onLine,
        get clientesFiltrados() {
            const q = this.normalizarBusqueda(this.buscarCliente);
            return this.clientes.filter(c => String(c.id) === this.clienteId || this.normalizarBusqueda(`${c.nombre} ${c.celular || ''} ${c.numero_cliente || ''}`).includes(q));
        },
        normalizarBusqueda(value) { return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
        cambiarCantidad(item, delta) { item.cantidad = Math.max(1, Number(item.cantidad || 1) + delta); this.ajustarColores(item); },
        guardar(event) {
            if (this.guardando) { event.preventDefault(); return; }
            const errores = this.validarCampos(event.target);
            if (!this.clienteId && !errores.some(error => error.campo?.name === 'cliente_id')) {
                errores.unshift({ mensaje: 'Selecciona un cliente. Si no tienes clientes, completa Crear cliente rápido.', campo: event.target.querySelector('#cliente_id') });
            }
            if (!this.items.length) errores.push({ mensaje: 'Agrega al menos una prenda a la orden.', campo: event.target.querySelector('#prenda_selector') });
            this.mostrarErrores(errores);
            if (errores.length || !this.online) { event.preventDefault(); return; }
            this.guardando = true;
        },
        selectedPrendaId: '',
        items: [],
        nextItemKey: 0,
        coloresDisponibles: ['Blanco', 'Negro', 'Azul', 'Rojo', 'Verde', 'Amarillo', 'Gris', 'Rosa', 'Cafe', 'Morado', 'Naranja', 'Beige', 'Violeta', 'Multicolor', 'Otro'],
        paymentOpen: false,
        selectedOrder: '',
        paymentAction: '',
        cancelConfirmOpen: false,
        cancelAction: '',
        orderSummaryOpen: false,
        selectedOrderSummary: {},
        modalEstatus: false,
        modalOrdenes: false,
        ordenPrintBase,
        isTouchDevice: false,
        isMobileViewport: false,

        init() {
            window.addEventListener('online', () => this.online = true);
            window.addEventListener('offline', () => this.online = false);
            window.addEventListener('pageshow', () => this.guardando = false);
            const restoreStatus = () => {
                if (window.location.hash === '#estatus-facturas') {
                    this.modalEstatus = true;
                    this.orderSummaryOpen = false;
                }
            };
            restoreStatus();
            this.modalOrdenes = window.location.hash === '#ordenes-recientes';
            window.addEventListener('hashchange', restoreStatus);
            this.actualizarDispositivo();
            window.addEventListener('resize', () => this.actualizarDispositivo());

            oldItems.forEach((item) => {
                const prendaId = Number(item.prenda_id || 0);
                const seleccionada = ['1', 1, true, 'true', 'on'].includes(item.selected) || Number(item.cantidad || 0) > 0;
                if (!prendaId || !seleccionada) return;
                this.agregarPrenda(prendaId, {
                    cantidad: Number(item.cantidad || 1),
                    precio_unitario: item.precio_unitario !== undefined && item.precio_unitario !== null && item.precio_unitario !== ''
                        ? Number(item.precio_unitario || 0)
                        : undefined,
                    colores: Array.isArray(item.colores) ? item.colores : this.normalizarColores(item.color_prenda || []),
                });
            });

            this.seleccionarCliente();
        },

        actualizarDispositivo() {
            const pointerCoarse = window.matchMedia('(pointer: coarse)').matches;
            this.isTouchDevice = pointerCoarse || (navigator.maxTouchPoints || 0) > 0;
            this.isMobileViewport = window.innerWidth < 1024;
        },

        seleccionarCliente() {
            this.clienteActual = this.clientes.find((cliente) => String(cliente.id) === String(this.clienteId)) || {};
        },

        openPayment(action, order) {
            this.paymentAction = action;
            this.selectedOrder = order;
            this.paymentOpen = true;
        },

        openCancelConfirm(action, order) {
            this.cancelAction = action;
            this.selectedOrder = order;
            this.cancelConfirmOpen = true;
        },

        // F3: Abrir modal resumen de orden
        openOrderSummary(facturaData) {
            this.selectedOrderSummary = facturaData;
            this.orderSummaryOpen = true;
        },

        datosPrenda(prendaId) {
            return this.prendas.find((prenda) => Number(prenda.id) === Number(prendaId)) || null;
        },
        nombrePrenda(prendaId) {
            return this.datosPrenda(prendaId)?.nombre || 'Prenda no disponible';
        },
        tipoPrenda(prendaId) {
            return this.datosPrenda(prendaId)?.tipo || 'Sin tipo';
        },

        agregarPrenda(prendaId = this.selectedPrendaId, valores = {}) {
            const id = Number(prendaId || 0);
            if (!id || this.items.some((item) => Number(item.prenda_id) === id)) return;

            const prenda = this.datosPrenda(id);
            if (!prenda) return;
            const cantidad = Math.max(1, Number(valores.cantidad || 1));
            const colores = this.normalizarColores(valores.colores || valores.color_prenda || []);
            while (colores.length < cantidad) colores.push('');

            this.items.push({
                key: this.nextItemKey++,
                prenda_id: id,
                cantidad,
                precio_unitario: valores.precio_unitario !== undefined
                    ? Math.max(0, Number(valores.precio_unitario || 0))
                    : Number(prenda.precio || 0),
                colores: colores.slice(0, cantidad),
            });

            this.selectedPrendaId = '';
        },

        eliminarPrenda(itemKey) {
            this.items = this.items.filter((item) => item.key !== itemKey);
        },

        precioUnitario(item) {
            if (!this.puedeEditarPrecios) {
                return Number(this.datosPrenda(item.prenda_id)?.precio || 0);
            }
            return Math.max(0, Number(item.precio_unitario || 0));
        },

        subtotalItem(item) {
            return Math.max(0, Number(item.cantidad || 0)) * this.precioUnitario(item);
        },
        ajustarColores(item) {
            const cantidad = Math.max(1, Number(item.cantidad || 1));
            item.cantidad = cantidad;
            item.colores = Array.isArray(item.colores) ? item.colores : [];
            while (item.colores.length < cantidad) item.colores.push('');
            if (item.colores.length > cantidad) item.colores = item.colores.slice(0, cantidad);
        },
        indicesPorCantidad(item) {
            this.ajustarColores(item);
            return Array.from({ length: Math.max(1, Number(item.cantidad || 1)) }, (_, index) => index);
        },
        coloresCompletos(item) {
            const cantidad = Math.max(1, Number(item.cantidad || 1));
            return Array.isArray(item.colores)
                && item.colores.length >= cantidad
                && item.colores.slice(0, cantidad).every((color) => this.coloresDisponibles.includes(String(color || '').trim()));
        },

        get prendasDisponibles() {
            const idsSeleccionados = this.items.map((item) => Number(item.prenda_id));
            return this.prendas.filter((prenda) => !idsSeleccionados.includes(Number(prenda.id)) && this.normalizarBusqueda(prenda.nombre + ' ' + (prenda.tipo || '')).includes(this.normalizarBusqueda(this.buscarPrenda)));
        },
        get totalPrendas() {
            return this.items.reduce((total, item) => total + Math.max(0, Number(item.cantidad || 0)), 0);
        },
        get totalFactura() {
            return this.items.reduce((total, item) => total + this.subtotalItem(item), 0);
        },
        get resumenPrendas() {
            return this.items.map((item) => ({
                key: item.key,
                nombre: this.nombrePrenda(item.prenda_id),
                cantidad: Math.max(0, Number(item.cantidad || 0)),
                subtotal: this.subtotalItem(item),
                color: item.colores.filter(Boolean).join(', '),
            }));
        },
        get puedeGuardarFactura() {
            return Boolean(this.clienteId) && this.items.length > 0 && this.items.every((item) => this.coloresCompletos(item));
        },
        get deviceLabel() {
            if (this.isTouchDevice && this.isMobileViewport) return 'Celular o pantalla táctil';
            if (this.isTouchDevice) return 'Pantalla táctil';
            if (this.isMobileViewport) return 'Pantalla pequeña';
            return 'Escritorio';
        },
        get deviceMessage() {
            if (this.isTouchDevice && this.isMobileViewport) return 'La interfaz se organiza en una sola columna y con botones más amplios para trabajar mejor desde celular.';
            if (this.isTouchDevice) return 'Se ampliaron controles y espacios para facilitar el uso en pantallas táctiles.';
            if (this.isMobileViewport) return 'La vista se compacta para pantallas pequeñas manteniendo todos los datos visibles.';
            return 'Controles optimizados para mouse y teclado, sin perder respuesta en ventanas reducidas.';
        },
        formatMoney(value) {
            return Number(value || 0).toLocaleString('es-CO');
        },
        formatInvoiceNumber(value) {
            return String(value || 1).padStart(6, '0');
        },
        normalizarColores(value) {
            const valores = Array.isArray(value) ? value : String(value || '').split(',');
            return valores.map((color) => String(color).trim()).filter((color) => this.coloresDisponibles.includes(color));
        },
    };
}
