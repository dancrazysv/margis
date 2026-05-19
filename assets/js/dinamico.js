// assets/js/dinamico.js
$(document).ready(function() {
    // Inicializar Select2
    $('.select2-init').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Buscar y seleccionar --',
        allowClear: true,
        language: {
            searching: function() { return "Buscando..."; },
            noResults: function() { return "No se encontraron resultados"; }
        }
    });
    
    // Convertir fechas a letras automáticamente
    $('.fecha-a-letras').on('change', function() {
        const input = $(this);
        const targetId = input.data('target');
        const fecha = input.val();
        
        if (fecha && targetId) {
            const d = new Date(fecha + 'T00:00:00');
            const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
            const fechaLetras = d.getDate() + " de " + meses[d.getMonth()] + " de " + d.getFullYear();
            $('#' + targetId).val(fechaLetras);
        }
    });
    
    // Manejar campos de funcionario (cargo + nombre)
    $(document).on('change', '.cargo-sel, .nombre-sel', function() {
        const varName = $(this).data('var');
        const cargo = $(`.cargo-sel[data-var="${varName}"]`).val() || "";
        const nombre = $(`.nombre-sel[data-var="${varName}"]`).val() || "";
        
        let texto = "";
        if (cargo || nombre) {
            texto = "ante " + (cargo + " " + nombre).trim();
        }
        $(`#final_${varName}`).val(texto);
    });
    
    // Manejar referencia legal
    $(document).on('change', '.tipo-ref', function() {
        const container = $(this).closest('.p-2');
        const tipo = $(this).val();
        
        container.find('.campos-doc').hide();
        container.find('.campos-partida-ext').hide();
        
        if (tipo === 'escritura' || tipo === 'acta') {
            container.find('.campos-doc').show();
        } else if (tipo === 'partida') {
            container.find('.campos-partida-ext').show();
        }
        construirReferenciaLegal(container);
    });
    
    function construirReferenciaLegal(container) {
        const tipo = container.find('.tipo-ref').val();
        const numero = container.find('.ref-numero').val();
        const anio = container.find('.ref-anio').val();
        const libro = container.find('.ref-libro').val();
        const asiento = container.find('.ref-asiento').val();
        const folio = container.find('.ref-folio').val();
        const tomo = container.find('.ref-tomo').val();
        const distrito = container.find('.ref-distrito option:selected').text();
        
        let resultado = "";
        
        if (tipo === 'escritura') {
            resultado = "certificación de testimonio de escritura pública número " + numero;
        } else if (tipo === 'acta') {
            resultado = "certificación de acta matrimonial número " + numero;
        } else if (tipo === 'partida') {
            resultado = "certificación de partida de matrimonio del Registro del Estado Familiar del " + distrito;
            if (asiento) resultado += ", con número de asiento " + asiento;
            if (folio) resultado += ", folio " + folio;
            if (tomo) resultado += ", tomo " + tomo;
            if (libro) resultado += ", libro " + libro;
            if (anio) resultado += ", del año " + anio;
        }
        
        container.find('.ref-final').val(resultado);
    }
    
    $(document).on('input change', '.ref-numero, .ref-anio, .ref-libro, .ref-asiento, .ref-folio, .ref-tomo, .ref-distrito', function() {
        construirReferenciaLegal($(this).closest('.p-2'));
    });
});

// Función para gestionar interfaz de matrimonio (EL/ELLA/AMBOS)
function gestionarInterfazMatrimonio() {
    const s = document.getElementById('sujeto_mat').value;
    const inputEl = document.getElementById('nombre_el');
    const inputElla = document.getElementById('nombre_ella');
    const selectLeyenda = document.getElementById('leyenda_tipo');
    const divLeyenda = document.getElementById('div_leyenda');
    const f2 = document.getElementById('foliacion_2');
    
    if (!inputEl) return;
    
    document.getElementById('div_nombre_el').style.opacity = "1";
    document.getElementById('div_nombre_ella').style.opacity = "1";
    if (divLeyenda) divLeyenda.style.opacity = "1";
    
    if (s === 'EL') {
        inputEl.disabled = true;
        inputEl.required = false;
        inputEl.value = "";
        document.getElementById('div_nombre_el').style.opacity = "0.5";
        
        inputElla.disabled = false;
        inputElla.required = true;
        
        if (selectLeyenda) {
            selectLeyenda.disabled = true;
            selectLeyenda.required = false;
        }
        if (divLeyenda) {
            divLeyenda.style.opacity = "0.4";
            divLeyenda.style.pointerEvents = "none";
        }
        if (f2) f2.style.display = 'none';
        
    } else if (s === 'ELLA') {
        inputElla.disabled = true;
        inputElla.required = false;
        inputElla.value = "";
        document.getElementById('div_nombre_ella').style.opacity = "0.5";
        
        inputEl.disabled = false;
        inputEl.required = true;
        
        if (selectLeyenda) {
            selectLeyenda.disabled = false;
            selectLeyenda.required = true;
        }
        if (divLeyenda) {
            divLeyenda.style.opacity = "1";
            divLeyenda.style.pointerEvents = "auto";
        }
        if (f2) f2.style.display = 'none';
        
    } else {
        inputEl.disabled = false;
        inputEl.required = true;
        inputElla.disabled = false;
        inputElla.required = true;
        
        if (selectLeyenda) {
            selectLeyenda.disabled = false;
            selectLeyenda.required = true;
        }
        if (divLeyenda) {
            divLeyenda.style.opacity = "1";
            divLeyenda.style.pointerEvents = "auto";
        }
        if (f2) f2.style.display = 'block';
    }
}

function toggleExterior() {
    const apExt = document.getElementById('ap_ext');
    if (apExt) {
        apExt.style.display = document.getElementById('leyenda_tipo').value === 'exterior' ? 'block' : 'none';
    }
}

function toggleRefFields() {
    const tipoRef = document.getElementById('tipo_ref');
    if (tipoRef) {
        const t = tipoRef.value;
        document.getElementById('campos_doc').style.display = (t === 'escritura' || t === 'acta') ? 'block' : 'none';
        document.getElementById('campos_partida_ext').style.display = (t === 'partida') ? 'flex' : 'none';
        armarRef();
    }
}

function armarRef() {
    const refFinal = document.getElementById('ref_final');
    if (!refFinal) return;
    
    const t = document.getElementById('tipo_ref').value;
    const n = document.getElementById('ref_num')?.value || '';
    const a = document.getElementById('ref_as')?.value || '';
    const f = document.getElementById('ref_fo')?.value || '';
    const li = document.getElementById('ref_li')?.value || '';
    const to = document.getElementById('ref_to')?.value || '';
    const y = document.getElementById('ref_an')?.value || '';
    const d = $('#ref_dist').select2('data')[0]?.text || '';
    
    let res = "";
    if (t === 'escritura') res = "certificación de testimonio de escritura pública número " + n;
    else if (t === 'acta') res = "certificación de acta matrimonial número " + n;
    else if (t === 'partida') {
        res = "certificación de partida de matrimonio del Registro del Estado Familiar del " + d;
        if (a) res += ", con número de asiento " + a;
        if (f) res += ", folio " + f;
        if (to) res += ", tomo " + to;
        if (li) res += ", libro " + li;
        if (y) res += ", del año " + y;
    }
    refFinal.value = res;
}

function convertirFecha(input, key) {
    if (!input.value) return;
    const d = new Date(input.value + 'T00:00:00');
    const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
    document.getElementById('hid_' + key).value = d.getDate() + " de " + meses[d.getMonth()] + " de " + d.getFullYear();
}