<?php
// templates/header.php - Con rutas absolutas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos de sesión
$user_id   = $_SESSION['user_id'] ?? 0;
$user_area = $_SESSION['user_area'] ?? 'MARGINADOR';
$nombre    = $_SESSION['nombre'] ?? 'Invitado';
$iniciales = $_SESSION['iniciales'] ?? 'U';

// Redirigir si no hay sesión
$archivo_actual = basename($_SERVER['PHP_SELF']);
if (!$user_id && $archivo_actual !== 'index.php') {
    header("Location: /margis/public/index.php");
    exit;
}

// Base URL fija
$base = '/margis/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marginación Digital - <?php echo $user_area; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <!-- jQuery PRIMERO -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        .navbar {
            background: linear-gradient(135deg, #1a3a5c 0%, #0d2b45 100%) !important;
            border-bottom: 3px solid #ffc107;
            padding: 0.5rem 1rem;
        }
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.6rem 1.2rem !important;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: #ffc107 !important;
            transform: translateY(-2px);
        }
        .navbar-nav .nav-link i {
            margin-right: 8px;
            font-size: 1.1rem;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
            margin-top: 0;
        }
        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-top: 5px;
        }
        .dropdown-item {
            padding: 8px 20px;
            transition: all 0.2s ease;
        }
        .dropdown-item i {
            margin-right: 10px;
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }
        .dropdown-item:hover {
            background: #1a3a5c;
            color: white;
            transform: translateX(5px);
        }
        .area-badge {
            background: #ffc107;
            color: #1a3a5c;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 800;
        }
        .user-info-box {
            background: rgba(255,255,255,0.1);
            padding: 5px 15px;
            border-radius: 30px;
            border-left: 3px solid #ffc107;
        }
        .btn-logout {
            border: 1px solid rgba(255,255,255,0.3);
            background: transparent;
            color: white;
            padding: 6px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-logout i {
            margin-right: 5px;
        }
        .btn-logout:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            transform: scale(1.05);
        }
        main {
            min-height: calc(100vh - 80px);
            padding: 20px;
        }
        
        /* Estilos para Select2 */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }
        .select2-search__field {
            font-size: 0.9rem;
        }
        
        /* Animación para el navbar */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .navbar {
            animation: fadeInDown 0.5s ease;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="<?php echo $base; ?>public/dashboard.php">
            <i class="bi bi-file-earmark-medical-fill me-2"></i>
            <strong>Marginación</strong> Digital
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base; ?>public/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                
                <?php if($user_area === 'MARGINADOR' || $user_area === 'ADMINISTRADOR'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base; ?>public/nueva.php">
                        <i class="bi bi-file-earmark-plus"></i> Nueva Marginación
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base; ?>public/combos.php">
                        <i class="bi bi-collection-fill"></i> Actos Múltiples
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base; ?>public/buscar.php">
                        <i class="bi bi-search"></i> Buscar
                    </a>
                </li>
                
                <?php if(in_array($user_area, ['CONTROL_CALIDAD', 'SUPERVISOR', 'ADMINISTRADOR'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $base; ?>public/control_calidad.php">
                        <i class="bi bi-check2-circle"></i> Control de Calidad
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if($user_area === 'ADMINISTRADOR'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear-fill"></i> Configuración
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="<?php echo $base; ?>admin/usuarios.php">
                                <i class="bi bi-people-fill"></i> Usuarios
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $base; ?>admin/plantillas.php">
                                <i class="bi bi-file-text-fill"></i> Plantillas
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $base; ?>admin/combos.php">
                                <i class="bi bi-collection-fill"></i> Combos
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo $base; ?>reports/estadisticas.php">
                                <i class="bi bi-graph-up"></i> Estadísticas
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center gap-3">
                <div class="user-info-box text-white d-none d-md-flex align-items-center gap-3">
                    <i class="bi bi-person-badge"></i>
                    <span class="area-badge">
                        <i class="bi bi-shield-check"></i> <?php echo $user_area; ?>
                    </span>
                    <span>
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($nombre); ?>
                    </span>
                    <span class="fw-bold text-warning">
                        <i class="bi bi-key"></i> <?php echo $iniciales; ?>
                    </span>
                </div>
                
                <a href="<?php echo $base; ?>actions/logout.php" class="btn-logout" onclick="return confirm('¿Cerrar sesión?');">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </div>
</nav>

<main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

<script>
// Función global para inicializar Select2
window.initSelect2 = function(selector, parent) {
    if (typeof $.fn.select2 === 'undefined') {
        console.log("Select2 no está cargado, reintentando...");
        setTimeout(function() { window.initSelect2(selector, parent); }, 200);
        return;
    }
    
    selector = selector || '.select2-init';
    var $elements = parent ? $(parent).find(selector) : $(selector);
    
    $elements.each(function() {
        var $select = $(this);
        // Evitar doble inicialización
        if ($select.data('select2')) {
            return;
        }
        
        try {
            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Buscar y seleccionar --',
                allowClear: true,
                language: 'es'
            });
        } catch(e) {
            console.log("Error inicializando Select2:", e);
        }
    });
};

// Función para inicializar Select2 con tags (permite escribir nuevos valores)
window.initSelect2Tags = function(selector, parent) {
    if (typeof $.fn.select2 === 'undefined') {
        console.log("Select2 no está cargado, reintentando...");
        setTimeout(function() { window.initSelect2Tags(selector, parent); }, 200);
        return;
    }
    
    selector = selector || '.select2-tag';
    var $elements = parent ? $(parent).find(selector) : $(selector);
    
    $elements.each(function() {
        var $select = $(this);
        if ($select.data('select2')) {
            return;
        }
        
        try {
            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $select.data('placeholder') || 'Escriba o seleccione...',
                language: 'es',
                tags: true,
                allowClear: true,
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term + ' (nuevo)',
                        newTag: true
                    };
                },
                templateResult: function(data) {
                    if (data.newTag) {
                        return $('<span class="text-success"><i class="bi bi-plus-circle-fill"></i> Crear: ' + data.text.replace(' (nuevo)', '') + '</span>');
                    }
                    return data.text;
                },
                templateSelection: function(data) {
                    return data.text.replace(' (nuevo)', '');
                }
            });
        } catch(e) {
            console.log("Error inicializando Select2 Tags:", e);
        }
    });
};

// Inicializar cuando el DOM esté listo
$(document).ready(function() {
    window.initSelect2();
    window.initSelect2Tags();
    
    // Observador para selects agregados dinámicamente
    const observer = new MutationObserver(function(mutations) {
        let necesitaInit = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if ($(node).find('.select2-init, .select2-tag').length || 
                        ($(node).is && ($(node).is('.select2-init') || $(node).is('.select2-tag')))) {
                        necesitaInit = true;
                    }
                });
            }
        });
        if (necesitaInit) {
            setTimeout(function() {
                window.initSelect2();
                window.initSelect2Tags();
            }, 100);
        }
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
});

// Funciones globales para referencia legal y fechas
window.toggleRefFields = function() {
    const t = document.getElementById('tipo_ref')?.value;
    const camposDoc = document.getElementById('campos_doc');
    const camposPartidaExt = document.getElementById('campos_partida_ext');
    if (camposDoc) camposDoc.style.display = (t === 'escritura' || t === 'acta') ? 'block' : 'none';
    if (camposPartidaExt) camposPartidaExt.style.display = (t === 'partida') ? 'flex' : 'none';
    if (typeof window.armarRef === 'function') window.armarRef();
};

window.armarRef = function() {
    const refFinal = document.getElementById('ref_final');
    if (!refFinal) return;
    const t = document.getElementById('tipo_ref')?.value;
    const n = document.getElementById('ref_num')?.value || '';
    const a = document.getElementById('ref_as')?.value || '';
    const f = document.getElementById('ref_fo')?.value || '';
    const li = document.getElementById('ref_li')?.value || '';
    const to = document.getElementById('ref_to')?.value || '';
    const y = document.getElementById('ref_an')?.value || '';
    const d = $('#ref_dist').find('option:selected')?.text() || '';
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
};

window.convertirFecha = function(input, key) {
    if (!input.value) return;
    const d = new Date(input.value + 'T00:00:00');
    const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
    const fechaLetras = d.getDate() + " de " + meses[d.getMonth()] + " de " + d.getFullYear();
    const targetLetras = document.getElementById('letras_' + key);
    const targetHid = document.getElementById('hid_' + key);
    if (targetLetras) targetLetras.value = fechaLetras;
    if (targetHid) targetHid.value = input.value;
};

window.toggleExterior = function() {
    const apExt = document.getElementById('ap_ext');
    const leyendaTipo = document.getElementById('leyenda_tipo');
    if (apExt && leyendaTipo) {
        apExt.style.display = leyendaTipo.value === 'exterior' ? 'block' : 'none';
    }
};

// Función para gestión de matrimonio
window.gestionarInterfazMatrimonio = function() {
    const s = document.getElementById('sujeto_mat')?.value;
    const inputEl = document.getElementById('nombre_el');
    const inputElla = document.getElementById('nombre_ella');
    const selectLeyenda = document.getElementById('leyenda_tipo');
    const divLeyenda = document.getElementById('div_leyenda');
    const f2 = document.getElementById('foliacion_2');

    if (!inputEl) return;
    
    const divNombreEl = document.getElementById('div_nombre_el');
    const divNombreElla = document.getElementById('div_nombre_ella');
    
    if (divNombreEl) divNombreEl.style.opacity = "1";
    if (divNombreElla) divNombreElla.style.opacity = "1";
    if (divLeyenda) divLeyenda.style.opacity = "1";

    if (s === 'EL') {
        inputEl.disabled = true;
        inputEl.required = false;
        inputEl.value = "";
        if (divNombreEl) divNombreEl.style.opacity = "0.5";
        
        if (inputElla) {
            inputElla.disabled = false;
            inputElla.required = true;
        }
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
        if (inputElla) {
            inputElla.disabled = true;
            inputElla.required = false;
            inputElla.value = "";
        }
        if (divNombreElla) divNombreElla.style.opacity = "0.5";

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
        if (inputElla) {
            inputElla.disabled = false;
            inputElla.required = true;
        }
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
};

// Event listeners para referencia legal
$(document).on('change', '#ref_dist, #tipo_ref', window.armarRef);
$(document).on('input', '#ref_num, #ref_an, #ref_li, #ref_as, #ref_fo, #ref_to', window.armarRef);
</script>