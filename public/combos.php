<?php
// public/combos.php - Generar Actos Múltiples
require_once '../config/db.php';
require_once '../core/plantillas.php';
include '../templates/header.php';

if ($_SESSION['user_area'] !== 'MARGINADOR' && $_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: dashboard.php?error=solo_marginadores");
    exit;
}

$combo_id = $_GET['combo_id'] ?? 0;
$anio_actual = ANIO_VIGENTE;
?>

<style>
    /* Estilos para el buscador con autocompletado */
    .card-filter {
        border: none;
        border-radius: 16px;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    }
    .filter-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 5px;
        display: block;
    }
    .combo-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .combo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    }
    .search-container {
        position: relative;
    }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: none;
    }
    .search-result-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }
    .search-result-item:hover, .search-result-item.active {
        background-color: #e7f1ff;
    }
    .search-result-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    .search-result-desc {
        font-size: 0.8rem;
        color: #666;
    }
    .search-result-badge {
        display: inline-block;
        font-size: 0.7rem;
        padding: 2px 6px;
        border-radius: 12px;
        background: #e9ecef;
        color: #495057;
        margin-top: 4px;
    }
    .loading-spinner {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    .clear-search {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
        display: none;
        z-index: 10;
    }
    .clear-search:hover {
        color: #666;
    }
    mark {
        background-color: #fff3cd;
        padding: 0;
        border-radius: 2px;
    }
    /* Estilos para Select2 */
    .select2-container {
        width: 100% !important;
        z-index: 1050;
    }
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0.375rem;
        min-height: 38px;
    }
</style>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-collection-fill"></i> Generar Múltiples Marginaciones</h5>
            <small>Seleccione el tipo de acto y genere todas las marginaciones relacionadas automáticamente</small>
        </div>
        <div class="card-body">
            
            <?php if ($combo_id == 0): ?>
                
                <!-- BUSCADOR DINÁMICO CON AUTOCOMPLETADO -->
                <div class="card card-filter mb-4 shadow-sm">
                    <div class="card-body p-4">
                        <label class="filter-label"><i class="bi bi-search"></i> Buscar Combo</label>
                        <div class="search-container">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchComboInput" class="form-control border-start-0" 
                                       placeholder="Buscar por nombre o descripción..." autocomplete="off">
                                <div class="loading-spinner">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                </div>
                                <span class="clear-search" id="clearSearchBtn"><i class="bi bi-x-circle-fill"></i></span>
                            </div>
                            <div id="searchResults" class="search-results"></div>
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class="bi bi-info-circle"></i> Comience a escribir para ver resultados. Use flechas ↑ ↓ y Enter.
                        </div>
                    </div>
                </div>
                
                <!-- CONTENEDOR DE RESULTADOS -->
                <div id="combosContainer">
                    <div class="text-center py-5" id="loadingCombos" style="display: none;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Cargando combos...</p>
                    </div>
                    <div id="combosGrid"></div>
                </div>
                
            <?php else: 
                // =========================================
                // FORMULARIO DEL COMBO SELECCIONADO
                // =========================================
                $stmt = $conn->prepare("SELECT * FROM combos_marginaciones WHERE id = ?");
                $stmt->bind_param("i", $combo_id);
                $stmt->execute();
                $combo = $stmt->get_result()->fetch_assoc();
                
                if (!$combo):
            ?>
                <div class="alert alert-danger">Combo no encontrado</div>
                <a href="combos.php" class="btn btn-primary">Volver a combos</a>
            <?php else:
                $stmt = $conn->prepare("
                    SELECT cp.*, pt.nombre_tramite, pt.cuerpo_legal, pt.requiere_conyuge, pt.requiere_leyenda, pt.tipo_asiento
                    FROM combo_plantillas cp
                    JOIN plantillas_textos pt ON cp.plantilla_id = pt.id
                    WHERE cp.combo_id = ?
                    ORDER BY cp.orden
                ");
                $stmt->bind_param("i", $combo_id);
                $stmt->execute();
                $plantillas_combo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $total_plantillas = count($plantillas_combo);
                $es_matrimonio = (strpos($combo['nombre_combo'], 'Matrimonio') !== false);
                $es_divorcio = (strpos($combo['nombre_combo'], 'Divorcio') !== false);
                $es_viudez = (strpos($combo['nombre_combo'], 'Viudez') !== false);
                $es_rectificacion = (strpos($combo['nombre_combo'], 'Rectificación') !== false);
                $es_identidad = (strpos($combo['nombre_combo'], 'Identidad') !== false);
            ?>
            
            <form method="POST" action="../actions/guardar_combo.php" id="form-combo">
                <input type="hidden" name="combo_id" value="<?= $combo_id ?>">
                <input type="hidden" name="total_plantillas" value="<?= $total_plantillas ?>">
                
                <div class="mb-3">
                    <a href="combos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver a combos
                    </a>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i> 
                    <strong><?= htmlspecialchars($combo['nombre_combo']) ?></strong><br>
                    <?= htmlspecialchars($combo['descripcion']) ?>
                    <hr class="my-2">
                    <strong>Se generarán <?= $total_plantillas ?> marginaciones:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach($plantillas_combo as $p): ?>
                        <li><?= htmlspecialchars($p['nombre_tramite']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <?php foreach($plantillas_combo as $index => $plantilla):
                    $es_el = (strpos($plantilla['nombre_tramite'], 'ÉL') !== false);
                    $es_ella = (strpos($plantilla['nombre_tramite'], 'ELLA') !== false);
                    $color = $es_el ? 'primary' : ($es_ella ? 'danger' : 'secondary');
                    $icono = $es_el ? 'bi-gender-male' : ($es_ella ? 'bi-gender-female' : 'bi-file-text');
                    $titulo = $es_el ? 'PARTIDA DE NACIMIENTO - ÉL' : ($es_ella ? 'PARTIDA DE NACIMIENTO - ELLA' : $plantilla['nombre_tramite']);
                ?>
                <div class="card mb-4 border-<?= $color ?>">
                    <div class="card-header bg-<?= $color ?> text-white">
                        <i class="<?= $icono ?>"></i> <?= $titulo ?>
                        <span class="badge bg-light text-dark ms-2">Orden: <?= $plantilla['orden'] ?></span>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="plantillas[<?= $index ?>][id]" value="<?= $plantilla['plantilla_id'] ?>">
                        <input type="hidden" name="plantillas[<?= $index ?>][nombre]" value="<?= htmlspecialchars($plantilla['nombre_tramite']) ?>">
                        <input type="hidden" name="plantillas[<?= $index ?>][requiere_partida]" value="<?= $plantilla['requiere_partida_propia'] ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="small fw-bold">Tipo Partida</label>
                                <select name="plantillas[<?= $index ?>][tipo_p]" class="form-select select2-init" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php 
                                    $tps = $conn->query("SELECT nombre_partida FROM tipo_partida WHERE grupo_partida = 1 ORDER BY nombre_partida ASC"); 
                                    while($t = $tps->fetch_assoc()): ?>
                                        <option value="<?= $t['nombre_partida'] ?>"><?= $t['nombre_partida'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Distrito / Municipio</label>
                                <select name="plantillas[<?= $index ?>][distrito]" class="form-select select2-init" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php 
                                    $municipios = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                                    while($m = $municipios->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($m['municipio']) ?>"><?= htmlspecialchars($m['municipio']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Año</label>
                                <input type="text" name="plantillas[<?= $index ?>][anio_p]" class="form-control" placeholder="Ej: 2024" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Libro</label>
                                <input type="text" name="plantillas[<?= $index ?>][libro_p]" class="form-control" placeholder="Ej: 1" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Partida</label>
                                <input type="text" name="plantillas[<?= $index ?>][n_partida]" class="form-control" placeholder="Ej: 1234" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Folio</label>
                                <input type="text" name="plantillas[<?= $index ?>][folio]" class="form-control" placeholder="Ej: 56" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Tomo</label>
                                <input type="text" name="plantillas[<?= $index ?>][tomo]" class="form-control" placeholder="Opcional">
                            </div>
                            <div class="col-md-12">
                                <label class="small fw-bold">Nombre Completo del Inscrito</label>
                                <input type="text" name="plantillas[<?= $index ?>][nombre]" class="form-control" required 
                                       placeholder="<?= $es_el ? 'Ej: JUAN CARLOS PEREZ MENDOZA' : ($es_ella ? 'Ej: MARIA ELENA GARCIA DE PEREZ' : 'Nombre completo') ?>">
                            </div>
                        </div>
                        
                        <?php
                        preg_match_all('/\{(.*?)\}/', $plantilla['cuerpo_legal'], $matches);
                        $variables = array_unique($matches[1]);
                        $variables_sistema = ['o_a', 'leyenda_apellido', 'fecha_hoy_letras', 'nombre_conyuge', 'nombre_el', 'nombre_ella', 'lugar', 'tipo_asiento_texto'];
                        $variables_adicionales = array_diff($variables, $variables_sistema);
                        if (!empty($variables_adicionales) && !$es_matrimonio && !$es_divorcio && !$es_viudez):
                        ?>
                        <div class="row g-3 mt-3 border-top pt-3">
                            <div class="col-12"><label class="fw-bold small">Datos Adicionales</label></div>
                            <?php foreach($variables_adicionales as $var): ?>
                            <div class="col-md-6">
                                <label class="small fw-bold"><?= ucwords(str_replace('_', ' ', $var)) ?></label>
                                <input type="text" name="plantillas[<?= $index ?>][vars][<?= $var ?>]" class="form-control" 
                                       placeholder="Ingrese <?= str_replace('_', ' ', $var) ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($es_matrimonio || $es_divorcio || $es_viudez): ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <i class="bi bi-calendar-event"></i> Datos del Acto Jurídico
                    </div>
                    <div class="card-body">
                        <?php if ($es_matrimonio): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small fw-bold">Lugar de la Boda</label>
                                <select name="lugar_boda" class="form-select select2-init" required>
                                    <option value="">-- Seleccione un municipio --</option>
                                    <?php 
                                    $municipios = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                                    while($m = $municipios->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($m['municipio']) ?>"><?= htmlspecialchars($m['municipio']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Fecha de la Boda</label>
                                <input type="date" name="fecha_boda" class="form-control" required>
                            </div>
                     <div class="col-md-4">
    <label class="small fw-bold">Funcionario que celebró</label>
    <div class="row g-2">
        <div class="col-md-6">
            <select name="cargo_funcionario" class="form-select select2-tag" data-placeholder="Seleccione o escriba un cargo...">
                <option value="">-- Seleccione o escriba --</option>
                <?php 
                $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                while($c = $cargos->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c['cargo']) ?>"><?= htmlspecialchars($c['cargo']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-6">
            <select name="nombre_funcionario" class="form-select select2-tag" data-placeholder="Seleccione o escriba un nombre...">
                <option value="">-- Seleccione o escriba --</option>
                <?php 
                $notarios = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                while($n = $notarios->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($n['nombre']) ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <div class="form-text text-muted small mt-1">
        <i class="bi bi-info-circle"></i> Puede seleccionar de la lista o escribir un valor nuevo
    </div>
</div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <label class="small fw-bold">Referencia Legal / Documento de Respaldo</label>
                                <div class="p-2 border rounded bg-light">
                                    <select id="tipo_ref" class="form-select select2-init mb-2" onchange="toggleRefFields()">
                                        <option value="">-- Tipo Documento --</option>
                                        <option value="escritura">Escritura Pública</option>
                                        <option value="acta">Acta Matrimonial</option>
                                        <option value="partida">Partida (Distrito Externo)</option>
                                    </select>
                                    <div id="campos_partida_ext" style="display:none;" class="row g-2">
                                        <div class="col-md-4"><input type="text" id="ref_an" class="form-control" placeholder="Año"></div>
                                        <div class="col-md-4"><input type="text" id="ref_li" class="form-control" placeholder="Libro"></div>
                                        <div class="col-md-4"><input type="text" id="ref_as" class="form-control" placeholder="As."></div>
                                        <div class="col-md-4"><input type="text" id="ref_fo" class="form-control" placeholder="Fol."></div>
                                        <div class="col-md-4"><input type="text" id="ref_to" class="form-control" placeholder="Tom."></div>
                                        <div class="col-md-12 mt-1">
                                            <select id="ref_dist" class="form-select select2-init">
                                                <option value="">-- Municipio/Distrito --</option>
                                                <?php $m=$conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC"); while($r=$m->fetch_assoc()): ?>
                                                    <option value='<?= htmlspecialchars($r['municipio']) ?>'><?= htmlspecialchars($r['municipio']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="campos_doc" style="display:none;"><input type="text" id="ref_num" class="form-control mb-2" placeholder="Número de documento"></div>
                                    <input type="hidden" name="referencia_legal" id="ref_final">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="small fw-bold">Leyenda de Apellidos (para ELLA)</label>
                                <select name="leyenda_tipo" id="leyenda_tipo" class="form-select select2-init" onchange="toggleExterior()">
                                    <option value="soltera">Seguirá usando sus apellidos de soltera</option>
                                    <option value="con_de">Usará "DE" + apellido del cónyuge</option>
                                    <option value="sin_de">Usará apellido del cónyuge sin "DE"</option>
                                    <option value="exterior">Exterior (Manual)</option>
                                </select>
                                <input type="text" name="apellidos_ext" id="ap_ext" class="form-control mt-2" style="display:none;" placeholder="Escriba apellidos manuales">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($es_divorcio): ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small fw-bold">Número de Oficio/Sentencia</label>
                                <input type="text" name="numero_oficio" class="form-control" placeholder="Ej: 123-2024" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Fecha de la Sentencia</label>
                                <input type="date" name="fecha_sentencia" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Juzgado que emitió</label>
                                <input type="text" name="juzgado" class="form-control" placeholder="Ej: Juzgado Primero de Familia" required>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($es_viudez): ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">¿Quién falleció?</label>
                                <select name="fallecido" class="form-select select2-init" required>
                                    <option value="">-- Seleccione --</option>
                                    <option value="EL">Él</option>
                                    <option value="ELLA">Ella</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Partida de Defunción N°</label>
                                <input type="text" name="partida_defuncion" class="form-control" placeholder="Ej: 1234" required>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-hash"></i> Control de Expediente
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="small fw-bold text-danger">Número de Trámite (Obligatorio):</label>
                                <div class="input-group">
                                    <input type="text" name="tramite_anio" class="form-control" style="max-width: 80px;" value="<?= $anio_actual ?>">
                                    <span class="input-group-text bg-white fw-bold">-</span>
                                    <input type="text" name="tramite_correlativo" class="form-control" placeholder="xxxx-xx" required 
                                           pattern="[0-9]+-[0-9]+" title="Formato: números-guion-número">
                                </div>
                                <div class="form-text">Ajuste el año si el trámite fue recibido en el período anterior.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Importante:</strong> Se generarán <?= $total_plantillas ?> marginaciones diferentes. 
                    Cada una recibirá un ID digital correlativo automático.
                </div>
                
                <div class="text-end">
                    <a href="combos.php" class="btn btn-secondary">Volver a combos</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-collection-fill"></i> Generar <?= $total_plantillas ?> Marginaciones
                    </button>
                </div>
            </form>
            
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Script específico para combos.php (Select2 ya se inicializa automáticamente desde el footer)
$(document).ready(function() {
    // Cargar combos al inicio
    loadCombos('');
    
    // Búsqueda en tiempo real
    let searchTimeout;
    $('#searchComboInput').on('input', function() {
        const term = $(this).val();
        $('#clearSearchBtn').toggle(term.length > 0);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadCombos(term), 300);
    });
    
    // Botón limpiar
    $('#clearSearchBtn').on('click', function() {
        $('#searchComboInput').val('').focus();
        $(this).hide();
        loadCombos('');
        $('#searchResults').hide();
        selectedIndex = -1;
    });
    
    // Cerrar dropdown al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            $('#searchResults').hide();
            selectedIndex = -1;
        }
    });
    
    // Navegación por teclado
    $('#searchComboInput').on('keydown', function(e) {
        const $items = $('.search-result-item');
        const count = $items.length;
        if (count === 0) return;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, count - 1);
                updateSelected($items);
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelected($items);
                break;
            case 'Enter':
                if (selectedIndex >= 0) {
                    e.preventDefault();
                    const comboId = $items.eq(selectedIndex).data('id');
                    if (comboId) window.location.href = '?combo_id=' + comboId;
                }
                break;
            case 'Escape':
                $('#searchResults').hide();
                selectedIndex = -1;
                break;
        }
    });
});

let selectedIndex = -1;

function updateSelected($items) {
    $items.removeClass('active');
    if (selectedIndex >= 0) {
        $items.eq(selectedIndex).addClass('active');
        $items.eq(selectedIndex)[0].scrollIntoView({ block: 'nearest' });
    }
}

function loadCombos(searchTerm) {
    const $container = $('#combosGrid');
    const $loading = $('#loadingCombos');
    const $results = $('#searchResults');
    
    $loading.show();
    $container.html('');
    $results.hide();
    selectedIndex = -1;
    
    $.ajax({
        url: '../actions/buscar_combos_ajax.php',
        type: 'GET',
        data: { search: searchTerm },
        dataType: 'json',
        success: function(resp) {
            $loading.hide();
            if (resp.success && resp.data.length > 0) {
                let html = '<div class="row">';
                resp.data.forEach(combo => {
                    let icono = getIcono(combo.nombre_combo);
                    html += `
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 border-2 combo-card">
                                <div class="card-body text-center">
                                    <div class="mb-3"><i class="bi ${icono} fs-1"></i></div>
                                    <h6 class="fw-bold">${escapeHtml(combo.nombre_combo)}</h6>
                                    <p class="small text-muted">${escapeHtml(combo.descripcion)}</p>
                                    <a href="?combo_id=${combo.id}" class="btn btn-primary btn-sm mt-2">
                                        Seleccionar <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $container.html(html);
                
                if (searchTerm.length > 0) {
                    showSearchResults(resp.data, searchTerm);
                }
            } else {
                let emptyHtml = '';
                if (searchTerm.length > 0) {
                    emptyHtml = `
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p>No se encontraron combos para "<strong>${escapeHtml(searchTerm)}</strong>"</p>
                            <button class="btn btn-outline-primary btn-sm" onclick="$('#clearSearchBtn').click()">Limpiar búsqueda</button>
                        </div>
                    `;
                } else {
                    emptyHtml = `<div class="text-center py-5"><i class="bi bi-collection fs-1 text-muted"></i><p>No hay combos disponibles</p></div>`;
                }
                $container.html(emptyHtml);
                if (searchTerm.length > 0) showNoResults(searchTerm);
            }
        },
        error: function(xhr) {
            $loading.hide();
            console.error(xhr.responseText);
            $container.html('<div class="alert alert-danger text-center">Error al cargar combos. Recargue la página.</div>');
        }
    });
}

function showSearchResults(combos, term) {
    let html = '';
    combos.forEach(c => {
        html += `
            <div class="search-result-item" data-id="${c.id}">
                <div class="search-result-title">${highlight(c.nombre_combo, term)}</div>
                <div class="search-result-desc">${highlight(c.descripcion, term)}</div>
                <span class="search-result-badge"><i class="bi ${getIcono(c.nombre_combo)}"></i> Click para seleccionar</span>
            </div>
        `;
    });
    $('#searchResults').html(html).show();
    $('.search-result-item').off('click').on('click', function() {
        window.location.href = '?combo_id=' + $(this).data('id');
    });
}

function showNoResults(term) {
    $('#searchResults').html(`<div class="search-result-item text-center text-muted">No se encontraron resultados para "${escapeHtml(term)}"</div>`).show();
}

function getIcono(nombre) {
    const map = {
        'Matrimonio Civil Nacional': 'bi-heart-fill text-danger',
        'Matrimonio Civil Extranjero': 'bi-globe2 text-info',
        'Divorcio': 'bi-heartbreak-fill text-warning',
        'Viudez (Muerte de Cónyuge)': 'bi-people-fill text-secondary',
        'Fallecimiento': 'bi-tree-fill text-dark',
        'Rectificación de Nombre': 'bi-pencil-square text-info',
        'Rectificación de Apellidos': 'bi-pencil-square text-info',
        'Identidad Personal': 'bi-person-badge text-primary'
    };
    return map[nombre] || 'bi-file-text text-primary';
}

function highlight(text, term) {
    if (!text) return '';
    if (!term) return escapeHtml(text);
    const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark>$1</mark>');
}

function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function escapeRegex(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Funciones para los campos dinámicos de matrimonio/divorcio
function toggleExterior() {
    const apExt = document.getElementById('ap_ext');
    const leyendaTipo = document.getElementById('leyenda_tipo');
    if (apExt && leyendaTipo) {
        apExt.style.display = leyendaTipo.value === 'exterior' ? 'block' : 'none';
    }
}

function toggleRefFields() {
    const tipoRef = document.getElementById('tipo_ref');
    const camposDoc = document.getElementById('campos_doc');
    const camposPartidaExt = document.getElementById('campos_partida_ext');
    
    if (!tipoRef) return;
    const t = tipoRef.value;
    
    if (camposDoc) {
        camposDoc.style.display = (t === 'escritura' || t === 'acta') ? 'block' : 'none';
    }
    if (camposPartidaExt) {
        camposPartidaExt.style.display = (t === 'partida') ? 'flex' : 'none';
    }
    armarRef();
}

function armarRef() {
    const tipoRef = document.getElementById('tipo_ref');
    if (!tipoRef) return;
    
    const t = tipoRef.value;
    const n = document.getElementById('ref_num')?.value || '';
    const a = document.getElementById('ref_as')?.value || '';
    const f = document.getElementById('ref_fo')?.value || '';
    const li = document.getElementById('ref_li')?.value || '';
    const to = document.getElementById('ref_to')?.value || '';
    const y = document.getElementById('ref_an')?.value || '';
    const distSelect = document.getElementById('ref_dist');
    const d = distSelect ? distSelect.options[distSelect.selectedIndex]?.text || '' : '';
    
    let res = "";
    if (t === 'escritura') {
        res = "certificación de testimonio de escritura pública número " + n;
    } else if (t === 'acta') {
        res = "certificación de acta matrimonial número " + n;
    } else if (t === 'partida') {
        res = "certificación de partida de matrimonio del Registro del Estado Familiar del " + d;
        if (a) res += ", con número de asiento " + a;
        if (f) res += ", folio " + f;
        if (to) res += ", tomo " + to;
        if (li) res += ", libro " + li;
        if (y) res += ", del año " + y;
    }
    
    const refFinal = document.getElementById('ref_final');
    if (refFinal) refFinal.value = res;
}

// Event listeners para referencia legal
$(document).on('change', '#ref_dist, #tipo_ref', armarRef);
$(document).on('input', '#campos_doc input, #campos_partida_ext input', armarRef);
</script>

<?php include '../templates/footer.php'; ?>