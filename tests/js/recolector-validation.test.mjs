import test from 'node:test';
import assert from 'node:assert/strict';
import recolectorForm from '../../resources/js/recolector.js';

const createForm = () => recolectorForm({ clientes: [], prendas: [], oldItems: [], facturas: [] });

test('identifies the garment and exact unit missing a color', () => {
    const state = createForm();
    const missing = { willValidate: true, validity: { valid: false, valueMissing: true }, dataset: { validationLabel: 'Camisa: color de la unidad 2' } };
    const valid = { willValidate: true, validity: { valid: true } };
    const errors = state.validarCampos({ elements: [valid, missing] });
    assert.equal(errors.length, 1);
    assert.equal(errors[0].mensaje, 'Falta completar: Camisa: color de la unidad 2.');
    assert.equal(errors[0].campo, missing);
});

test('blocks invalid submissions without locking the save button', () => {
    const state = createForm();
    state.clienteId = '1';
    state.items = [{ prenda_id: 1 }];
    state.online = true;
    const missing = { willValidate: true, validity: { valid: false, valueMissing: true }, dataset: { validationLabel: 'Color' } };
    state.mostrarErrores = errors => state.erroresFormulario = errors;
    let prevented = false;
    state.guardar({ target: { elements: [missing] }, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(state.guardando, false);
});

test('shows all missing client fields together', () => {
    const state = createForm();
    state.mostrarErrores = errors => state.erroresFormulario = errors;
    let prevented = false;
    state.guardarCliente({ target: { elements: ['Nombre del cliente', 'Barrio del cliente'].map(label => ({ willValidate: true, validity: { valid: false, valueMissing: true }, dataset: { validationLabel: label } })) }, preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(state.erroresFormulario.length, 2);
});
