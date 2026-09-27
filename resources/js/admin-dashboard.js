export default function adminDashboard({ ordenPrintBase }) {
    return {
        
        buscarUsuario: '',
        buscarCliente: '',
        errores: [],
        campoError: null,
        coincide(texto, busqueda) {
            const normalizar = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            return normalizar(texto).includes(normalizar(busqueda).trim());
        },
        enfocar(campo) {
            this.campoError?.removeAttribute('aria-invalid');
            this.campoError?.classList.remove('ring-2', 'ring-rose-500');
            this.campoError = campo;
            campo?.setAttribute('aria-invalid', 'true');
            campo?.classList.add('ring-2', 'ring-rose-500');
            campo?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            campo?.focus({ preventScroll: true });
        },
        init() {
            this.modalFacturas = new URLSearchParams(window.location.search).has('facturas_page') || window.location.hash === '#facturas';
            const etiquetas = { name: 'Nombre completo', email: 'Correo', password: 'Contraseña', password_confirmation: 'Confirmar contraseña', rol: 'Rol', monto: 'Monto del gasto', concepto: 'Concepto del gasto', fecha: 'Fecha', metodo_pago: 'Método de pago' };
            this.$root.addEventListener('invalid', event => {
                event.preventDefault();
                const form = event.target.form;
                this.errores = Array.from(form.elements).filter(c => c.willValidate && !c.validity.valid).map(campo => ({
                    campo,
                    mensaje: (etiquetas[campo.name] || campo.getAttribute('placeholder') || campo.name) + ': ' + (campo.validity.valueMissing ? 'completa este dato.' : campo.validationMessage),
                }));
                if (event.target === this.errores[0]?.campo) this.enfocar(event.target);
            }, true);
            this.$root.addEventListener('input', event => {
                if (event.target === this.campoError && event.target.validity?.valid) this.enfocar(null);
                this.errores = this.errores.filter(error => !error.campo.validity.valid);
            });
        },
        paymentOpen: false,
        cancelOpen: false,
        orderSummaryOpen: false,
        delegarOpen: false,
        selectedProducciones: [],
        selectedOrder: '',
        paymentAction: '',
        cancelAction: '',
        selectedOrderSummary: {},
        ordenPrintBase,
        delegarClienteId: null,
        delegarClienteNombre: '',
        delegarAction: '',

        
        modalUsuarios: false,
        modalFacturas: false,
        modalQuincenas: false,
        modalComisiones: false,
        modalGastos: false,
        modalIncongruencias: false,
        modalDelegacion: false,
        modalPrendasMes: false,
        modalRegistrosActivos: false,

        allProduccionesSelected(ids) {
            return ids.length > 0 && ids.every((id) => this.selectedProducciones.includes(String(id)));
        },
        toggleAllProducciones(ids, checked) {
            this.selectedProducciones = checked ? ids.map(String) : [];
        },
        openPayment(action, order) {
            this.paymentAction = action;
            this.selectedOrder = order;
            this.paymentOpen = true;
        },
        openCancel(action, order) {
            this.cancelAction = action;
            this.selectedOrder = order;
            this.cancelOpen = true;
        },
        openOrderSummary(data) {
            this.selectedOrderSummary = data;
            this.orderSummaryOpen = true;
        },
        openDelegar(clienteId, clienteNombre, action) {
            this.delegarClienteId = clienteId;
            this.delegarClienteNombre = clienteNombre;
            this.delegarAction = action;
            this.delegarOpen = true;
        },
        copyEnterpriseCode(code) {
            navigator.clipboard.writeText(code);
        }
    };
}
