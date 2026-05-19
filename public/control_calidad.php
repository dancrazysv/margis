<?php
require_once '../config/db.php';
include '../templates/header.php';

// Protección de Ruta
if (!in_array($_SESSION['user_area'], ['CONTROL_CALIDAD', 'SUPERVISOR', 'ADMINISTRADOR'])) {
    header("Location: dashboard.php?error=sin_permiso");
    exit;
}

// 1. OBTENER INICIALES DEL USUARIO ACTUAL (Para Estadísticas Personales)
$mis_iniciales = $_SESSION['iniciales'] ?? '';

// PARÁMETRO DE BÚSQUEDA (FILTRO)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- CONSULTAS PARA CARDS DE ESTADÍSTICAS ---
// Aprobadas hoy
$count_aprobadas = $conn->query("SELECT COUNT(*) FROM margi WHERE usuario_reviso = '$mis_iniciales' AND DATE(fecha_revision) = CURDATE() AND estado = 'CERRADO'")->fetch_row()[0];

// Devueltas hoy
$count_devueltas = $conn->query("SELECT COUNT(*) FROM margi WHERE usuario_reviso = '$mis_iniciales' AND observaciones_qc LIKE '%(" . date('d/m/Y') . ")%' AND estado = 'OBSERVADO'")->fetch_row()[0];

// Pendientes: Registros que están esperando en revisión
$count_pendientes = $conn->query("SELECT COUNT(*) FROM margi WHERE estado = 'EN_REVISION'")->fetch_row()[0];

// --- CONSTRUCCIÓN DE LA CONSULTA PRINCIPAL ---
$condicion_estado = empty($search) ? "m.estado = 'EN_REVISION'" : "1=1";

$sql = "SELECT m.*, u.distrito as distrito_usuario 
        FROM margi m 
        LEFT JOIN usuarios u ON (m.usuario_creo = u.iniciales OR m.Iniciales1 = u.iniciales)
        WHERE $condicion_estado";

if ($search !== '') {
    $sql .= " AND (CONCAT(m.LibroO, '-', m.NMargi1) LIKE '%$search%' OR m.num_tramite LIKE '%$search%' OR m.busquedalf LIKE '%$search%')";
}

$sql .= " ORDER BY m.id ASC";
$res = $conn->query($sql);
?>

<style>
   body { background: #f0f2f5; }
    
    /* Tarjetas de estadísticas */
    .stat-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        cursor: pointer;
        overflow: hidden;
        position: relative;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    }
    
    .stat-card .stat-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 3.5rem;
        opacity: 0.15;
    }
    
    .stat-card h3 {
        font-weight: 800;
        margin: 0;
        font-size: 2rem;
    }
    
    .stat-card p {
        margin: 0;
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    /* Card de búsqueda */
    .card-filter {
        border: none;
        border-radius: 16px;
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    }
    
    
    /* Tarjetas de registros */
    .marginacion-card {
        border: none;
        border-radius: 16px;
        transition: all 0.2s ease;
        background: white;
    }
    
    .marginacion-card:hover {
        box-shadow: 0 8px 25px -8px rgba(0,0,0,0.15);
    }
    
    .pre-text {
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
       
        background: #f8f9fa;
        border-radius: 12px;
        padding: 15px;
        border: 1px solid #e9ecef;
        max-height: 200px;
        overflow-y: auto;
        line-height: 1.5;
    }
    
    .filter-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: 5px;
        display: block;
    }
    
    /* Badges de estado */
    .badge-estado {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.7rem;
    }
    
    /* Modal Visor */
    .modal-backdrop.show { opacity: 0.90; }
    .texto-marginacion-preview { cursor: zoom-in; transition: all 0.2s ease; border: 1px solid #dee2e6; }
    .texto-marginacion-preview:hover { background-color: #f0f7ff !important; border-color: #0d6efd; }
    
    .visor-container { display: flex; gap: 0; min-height: 75vh; }
    .visor-sidebar { flex: 0 0 350px; background: #f8f9fa; padding: 25px; border-right: 2px solid #dee2e6; max-height: 80vh; overflow-y: auto; }
    .visor-main { flex: 1; background: #fff; }
    .text-grande { font-family: 'Courier New', monospace; font-size: 1.45rem; line-height: 1.6; white-space: pre-wrap; color: #000; padding: 40px; }
    
    .visor-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #6c757d; margin-bottom: 2px; }
    .visor-value { font-size: 1rem; font-weight: 600; color: #212529; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
    .obs-box-qc { background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 12px; font-size: 0.85rem; border-radius: 4px; margin-top: 10px; color: #856404; }
    
    /* Paginación */
    .pagination .page-link {
        border-radius: 10px;
        margin: 0 3px;
        color: #1a3a5c;
        border: none;
        padding: 8px 14px;
    }
    
    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #1a3a5c, #0d2b45);
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .stat-card h3 { font-size: 1.5rem; }
        .pre-text { font-size: 0.8rem; }
    }
</style>

<div class="container-fluid mt-4 px-4">
    
    <!-- ========================================= -->
    <!-- TARJETAS DE ESTADÍSTICAS                  -->
    <!-- ========================================= -->
    <div class="row g-3 mb-4">
        
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card bg-white text-dark border-start border-5 border-primary">
                <div class="card-body">
                    <div class="stat-icon text-primary"><i class="bi bi-check-all"></i></div>
                    <p class="text-primary">Mis Aprobadas Hoy</p>
                    <h3><?php echo number_format($count_aprobadas); ?></h3>
                    <small class="text-muted">Registros aprobados hoy</small>
                </div>
            </div>
        </div>

 <div class="col-sm-6 col-lg-4">
           <div class="card stat-card bg-white text-dark border-start border-5 border-warning">
                <div class="card-body">
                    <div class="stat-icon text-warning"><i class="bi bi-arrow-counterclockwise"></i></div>
                    <p class="text-warning">Mis Devueltas Hoy</p>
                    <h3><?php echo number_format($count_devueltas); ?></h3>
                    <small class="text-muted">Registros devueltos hoy</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card stat-card bg-white text-dark border-start border-5 border-info">
                <div class="card-body">
                    <div class="stat-icon text-info"><i class="bi bi-funnel"></i></div>
                    <p class="text-info">Pendientes en Cola</p>
                    <h3><?php echo number_format($count_pendientes); ?></h3>
                    <small class="text-muted">Esperando revisión</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========================================= -->
    <!-- FILTROS Y BÚSQUEDA                        -->
    <!-- ========================================= -->
    <div class="card card-filter mb-4 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="filter-label"><i class="bi bi-search"></i> Búsqueda General</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Filtrar por ID (Ej: 2026-3) o Número de Trámite..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark px-4 w-100">
                            <i class="bi bi-funnel-fill"></i> Aplicar Filtro
                        </button>
                        <a href="control_calidad.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-eraser-fill"></i> Restablecer
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ========================================= -->
    <!-- BANDEJA DE REVISIÓN                       -->
    <!-- ========================================= -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="text-primary fw-bold mb-0"><i class="bi bi-shield-check-fill me-2"></i>Bandeja de Revisión Técnica</h4>
            <?php if ($res && $res->num_rows > 0): ?>
                <span class="badge bg-secondary px-3 py-2 mt-2">
                    <i class="bi bi-files"></i> <?php echo $res->num_rows; ?> registro(s) encontrado(s)
                </span>
            <?php endif; ?>
            <?php if (!empty($search)): ?>
                <span class="badge bg-info px-3 py-2 mt-2 ms-2">
                    <i class="bi bi-search"></i> Buscando: "<?php echo htmlspecialchars($search); ?>"
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ========================================= -->
    <!-- RESULTADOS Y LISTADO                      -->
    <!-- ========================================= -->
    
    <?php if ($res && $res->num_rows > 0): ?>
        <div class="row">
            <?php while ($row = $res->fetch_assoc()): 
                $id_dig = ($row['LibroO'] >= 2026) ? $row['LibroO']."-".$row['NMargi1'] : $row['busquedalf'];
                $digitador = !empty($row['usuario_creo']) ? $row['usuario_creo'] : ($row['Iniciales1'] ?? 'N/A');
                $estado_badge = ($row['estado'] == 'CERRADO') ? 'success' : 'danger';
                $fecha_creacion = date('d/m/Y H:i', strtotime($row['FechaC']));
                
                $datos_json = json_encode([
                    'id' => $row['id'],
                    'id_display' => $id_dig,
                    'num_tramite' => $row['num_tramite'] ?? 'N/A',
                    'tipo' => $row['TipoP'],
                    'partida' => $row['NPartida'],
                    'libro' => $row['LibroP'],
                    'folio' => $row['FolioO'],
                    'anio_p' => $row['AnioP'],
                    'lugar' => $row['lugar'],
                    'digitador' => $digitador,
                    'distrito' => $row['distrito_usuario'] ?? 'N/A',
                    'fecha_ingreso' => $fecha_creacion,
                    'texto' => $row['TxtMargi1'],
                    'estado' => $row['estado']
                ]);
            ?>
                <div class="col-12 mb-4">
                    <div class="card marginacion-card shadow-sm border-start border-5 border-<?php echo $estado_badge; ?>">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3 border-bottom">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge-estado bg-<?php echo $estado_badge; ?> text-white">
                                    <?php echo $row['estado']; ?>
                                </span>
                                <strong class="fs-5">ID: <?php echo $id_dig; ?></strong>
                                <span class="badge bg-warning text-dark border">
                                    <i class="bi bi-hash"></i> <?php echo htmlspecialchars($row['num_tramite'] ?? 'N/A'); ?>
                                </span>
                                <span class="text-muted small">
                                    <i class="bi bi-clock"></i> <?php echo $fecha_creacion; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 border-end">
                                    <p class="filter-label mb-2"><i class="bi bi-journal-bookmark-fill"></i> Referencias</p>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-2"><strong>Tipo Partida:</strong> <?php echo $row['TipoP']; ?></li>
                                        <li class="mb-2"><strong>Partida N°:</strong> <?php echo $row['NPartida']; ?></li>
                                        <li class="mb-2"><strong>Libro:</strong> <?php echo $row['LibroP']; ?></li>
                                        <li class="mb-2"><strong>Folio:</strong> <?php echo $row['FolioO']; ?></li>
                                        <?php if(!empty($row['TomoP'])): ?>
                                        <li class="mb-2"><strong>Tomo:</strong> <?php echo $row['TomoP']; ?></li>
                                        <?php endif; ?>
                                        <li class="mb-2"><strong>Año Partida:</strong> <?php echo $row['AnioP']; ?></li>
                                        <li class="mb-2"><strong>Lugar:</strong> <?php echo htmlspecialchars($row['lugar']); ?></li>
                                        <hr class="my-2">
                                        <li class="text-primary"><strong>Digitó:</strong> 
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($digitador); ?></span>
                                        </li>
                                        <li class="text-dark small mt-1"><strong>Distrito:</strong> 
                                            <?php echo htmlspecialchars($row['distrito_usuario'] ?? 'N/A'); ?>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-md-6">
                                    <p class="filter-label mb-2"><i class="bi bi-text-paragraph"></i> Texto de la Marginación</p>
                                    <div class="pre-text texto-marginacion-preview" 
                                         onclick='ampliarTexto(<?php echo $row['id']; ?>, <?php echo $datos_json; ?>)'>
                                        <?php echo nl2br(htmlspecialchars($row['TxtMargi1'])); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 d-flex flex-column justify-content-center gap-2">
                                    <?php if ($row['estado'] == 'EN_REVISION'): ?>
                                    <button class="btn btn-success py-2 fw-bold shadow-sm" onclick="procesar('APROBAR', <?php echo $row['id']; ?>)">
                                        <i class="bi bi-check-circle-fill me-1"></i> Aprobar
                                    </button>
                                    <button class="btn btn-outline-danger py-2 shadow-sm" onclick="abrirModalDevolver(<?php echo $row['id']; ?>, '<?php echo $id_dig; ?>')">
                                        <i class="bi bi-arrow-return-left me-1"></i> Devolver
                                    </button>
                                    <?php else: ?>
                                    <div class="alert alert-info text-center mb-0">
                                        <i class="bi bi-info-circle"></i> Este registro ya fue procesado
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox-fill fs-1 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">No se encontraron registros en revisión</h5>
                <p class="text-muted small">
                    <?php if (!empty($search)): ?>
                        Intente con otros criterios de búsqueda o <a href="control_calidad.php">limpie los filtros</a>.
                    <?php else: ?>
                        No hay registros pendientes de revisión en este momento.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ========================================= -->
<!-- MODALES                                    -->
<!-- ========================================= -->

<!-- Modal Visor Detallado -->
<div class="modal fade" id="modalVisor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white border-bottom border-warning border-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye-fill me-2 text-warning"></i> REVISIÓN DETALLADA: <span id="v_id_display"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="visor-container">
                    <div class="visor-sidebar">
                        <div class="visor-label">Número de Trámite</div>
                        <div class="visor-value text-primary" id="v_tramite"></div>
                        
                        <div class="visor-label">Tipo de Partida</div>
                        <div class="visor-value" id="v_tipo"></div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="visor-label">Partida</div>
                                <div class="visor-value" id="v_partida"></div>
                            </div>
                            <div class="col-6">
                                <div class="visor-label">Libro</div>
                                <div class="visor-value" id="v_libro"></div>
                            </div>
                            <div class="col-6">
                                <div class="visor-label">Folio</div>
                                <div class="visor-value" id="v_folio"></div>
                            </div>
                            <div class="col-6">
                                <div class="visor-label">Año Part.</div>
                                <div class="visor-value" id="v_anio"></div>
                            </div>
                        </div>

                        <div class="visor-label">Lugar de Inscripción</div>
                        <div class="visor-value" id="v_lugar"></div>
                        
                        <hr>
                        <div class="visor-label">Digitador</div>
                        <div class="visor-value"><span class="badge bg-primary px-3" id="v_digitador"></span></div>
                        
                        <div class="visor-label">Distrito</div>
                        <div class="visor-value text-success fw-bold" id="v_distrito"></div>
                        
                        <div class="visor-label">Fecha Ingreso Sistema</div>
                        <div class="visor-value small" id="v_fecha_ingreso"></div>
                    </div>

                    <div class="visor-main">
                        <div id="v_texto" class="text-grande"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                <div class="ms-auto gap-2 d-flex" id="visor_botones_accion">
                    <button class="btn btn-outline-danger px-4 fw-bold" id="btn_v_devolver">DEVOLVER</button>
                    <button class="btn btn-success px-5 fw-bold" id="btn_v_aprobar">APROBAR</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Devolver -->
<div class="modal fade" id="modalDevolver" tabindex="-1" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-danger border-2">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Motivo de la Devolución: <span id="modal_id_text"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="obs_id_margi">
                <textarea id="txt_observacion" class="form-control" rows="6" placeholder="Escriba los errores encontrados..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger px-4" onclick="procesar('DEVOLVER')">Enviar a Corrección</button>
            </div>
        </div>
    </div>
</div>

<script>
const modalDevolver = new bootstrap.Modal(document.getElementById('modalDevolver'));
const modalVisor = new bootstrap.Modal(document.getElementById('modalVisor'));

function ampliarTexto(id, datos) {
    document.getElementById('v_id_display').innerText = datos.id_display;
    document.getElementById('v_tramite').innerText = datos.num_tramite;
    document.getElementById('v_tipo').innerText = datos.tipo;
    document.getElementById('v_partida').innerText = datos.partida;
    document.getElementById('v_libro').innerText = datos.libro;
    document.getElementById('v_folio').innerText = datos.folio;
    document.getElementById('v_anio').innerText = datos.anio_p;
    document.getElementById('v_lugar').innerText = datos.lugar;
    document.getElementById('v_digitador').innerText = datos.digitador;
    document.getElementById('v_distrito').innerText = datos.distrito; 
    document.getElementById('v_fecha_ingreso').innerText = datos.fecha_ingreso;
    document.getElementById('v_texto').innerText = datos.texto;

    if (datos.estado === 'EN_REVISION') {
        document.getElementById('visor_botones_accion').style.display = 'flex';
        document.getElementById('btn_v_aprobar').onclick = () => { modalVisor.hide(); procesar('APROBAR', id); };
        document.getElementById('btn_v_devolver').onclick = () => { modalVisor.hide(); abrirModalDevolver(id, datos.id_display); };
    } else {
        document.getElementById('visor_botones_accion').style.display = 'none';
    }

    modalVisor.show();
}

function abrirModalDevolver(id, displayId) {
    document.getElementById('obs_id_margi').value = id;
    document.getElementById('modal_id_text').innerText = displayId;
    document.getElementById('txt_observacion').value = "";
    modalDevolver.show();
}

function procesar(accion, id = null) {
    let observation = "";
    if (accion === 'DEVOLVER') {
        id = document.getElementById('obs_id_margi').value;
        observation = document.getElementById('txt_observacion').value;
        if (!observation.trim()) { alert("Por favor, especifique el motivo."); return; }
    }
    if (!confirm(`¿Confirmar acción: ${accion}?`)) return;
    $.ajax({
        url: '../actions/procesar_calidad.php',
        method: 'POST',
        data: { id: id, accion: accion, observacion: observation },
        success: function(r) { 
            let response = (typeof r === 'string') ? JSON.parse(r) : r;
            if (response.status === 'success') {
                location.reload(); 
            } else {
                alert("Error: " + response.message); 
            }
        },
        error: function() {
            alert("Error en la comunicación con el servidor.");
        }
    });
}
</script>

<?php include '../templates/footer.php'; ?>