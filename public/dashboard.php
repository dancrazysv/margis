<?php 
require_once '../config/db.php'; 
include '../templates/header.php'; 

// 1. PARÁMETROS DE FILTRADO
$q = trim($_GET['q'] ?? '');
$f_inicio = $_GET['f_inicio'] ?? '';
$f_fin = $_GET['f_fin'] ?? '';
$ver_todos = isset($_GET['ver_todos']) ? true : false;
$estado_filtro = $_GET['estado'] ?? '';
$mis_iniciales = $_SESSION['iniciales'] ?? ''; 
$rol_usuario = $_SESSION['user_area'] ?? 'MARGINADOR';

// 2. CONFIGURACIÓN DE PAGINACIÓN
$registros_por_pagina = 15;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 3. FUNCIÓN PARA RESALTAR TEXTO
function resaltar($texto, $busqueda) {
    if (empty($busqueda)) return htmlspecialchars($texto);
    return preg_replace('/(' . preg_quote(htmlspecialchars($busqueda), '/') . ')/i', '<mark class="p-0 bg-warning text-dark">$1</mark>', htmlspecialchars($texto));
}

// 4. CONSTRUCCIÓN DE LA CONSULTA
$condiciones = [];
$params = [];
$types = "";

// Filtro por usuario (si no ver todos)
if (!$ver_todos) {
    $condiciones[] = "(m.usuario_creo = ? OR m.Iniciales1 = ?)";
    $params[] = $mis_iniciales; 
    $params[] = $mis_iniciales;
    $types .= "ss";
}

// Filtro por búsqueda general
if (!empty($q)) {
    if (strpos($q, '--') !== false) {
        $condiciones[] = "m.libro_nmargi_concat = ?";
        $params[] = $q; 
        $types .= "s";
    } elseif (preg_match('/^\d{4}-\d+$/', $q)) {
        $q_convert = str_replace('-', '--', $q);
        $condiciones[] = "m.libro_nmargi_concat = ?";
        $params[] = $q_convert; 
        $types .= "s";
    } elseif (preg_match('/^\d{3}-\d+$/', $q)) {
        $condiciones[] = "m.busquedalf = ?";
        $params[] = $q; 
        $types .= "s";
    } else {
        $condiciones[] = "(m.TxtMargi1 LIKE ? OR m.NPartida LIKE ? OR m.LibroP LIKE ? OR m.num_tramite LIKE ? OR m.busquedalf LIKE ?)";
        $term = "%$q%";
        $params[] = $term; 
        $params[] = $term; 
        $params[] = $term; 
        $params[] = $term;
        $params[] = $term;
        $types .= "sssss";
    }
}

// Filtro por fechas
if ($f_inicio && $f_fin) {
    $condiciones[] = "DATE(m.FechaC) BETWEEN ? AND ?";
    $params[] = $f_inicio; 
    $params[] = $f_fin;
    $types .= "ss";
}

// Filtro por estado (desde tarjetas de estadísticas)
if (!empty($estado_filtro)) {
    $condiciones[] = "m.estado = ?";
    $params[] = $estado_filtro;
    $types .= "s";
}

// =====================================================
// CONDICIÓN POR DEFECTO: Mostrar solo registros DIGITADOS
// =====================================================
$condiciones_listado = $condiciones;
if (empty($q) && empty($f_inicio) && empty($f_fin) && empty($estado_filtro) && !$ver_todos) {
    $condiciones_listado[] = "m.estado = 'DIGITADO'";
}
$where_sql = count($condiciones_listado) > 0 ? "WHERE " . implode(" AND ", $condiciones_listado) : "";

// 5. ESTADÍSTICAS DINÁMICAS
$where_stats = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : "";

function getStat($conn, $where, $estado, $types, $params) {
    $sql = "SELECT COUNT(*) as total FROM margi m $where AND m.estado = ?";
    $st = $conn->prepare($sql);
    
    if (!$st) {
        return 0;
    }
    
    $tipos = $types . "s";
    $todos_params = array_merge($params, [$estado]);
    
    if (!empty($todos_params)) {
        $st->bind_param($tipos, ...$todos_params);
    }
    $st->execute();
    $result = $st->get_result();
    if ($result) {
        return $result->fetch_row()[0];
    }
    return 0;
}

function getStatIn($conn, $where, $estados, $types, $params) {
    if (empty($estados)) return 0;
    
    $placeholders = implode(',', array_fill(0, count($estados), '?'));
    $sql = "SELECT COUNT(*) as total FROM margi m $where AND m.estado IN ($placeholders)";
    $st = $conn->prepare($sql);
    
    if (!$st) {
        return 0;
    }
    
    $tipos = $types . str_repeat('s', count($estados));
    $todos_params = array_merge($params, $estados);
    
    if (!empty($todos_params)) {
        $st->bind_param($tipos, ...$todos_params);
    }
    $st->execute();
    $result = $st->get_result();
    if ($result) {
        return $result->fetch_row()[0];
    }
    return 0;
}

$c_dig = getStat($conn, $where_stats, 'DIGITADO', $types, $params);
$c_obs = getStat($conn, $where_stats, 'OBSERVADO', $types, $params);
$c_rev = getStat($conn, $where_stats, 'EN_REVISION', $types, $params);
$c_cer = getStatIn($conn, $where_stats, ['CERRADO', 'Activa'], $types, $params);
$total_mis_registros = $c_dig + $c_obs + $c_rev + $c_cer;

// 6. CONSULTA PRINCIPAL CON PAGINACIÓN
$sql = "SELECT m.*, u.distrito as distrito_usuario 
        FROM margi m 
        LEFT JOIN usuarios u ON (m.usuario_creo = u.iniciales OR m.Iniciales1 = u.iniciales)
        $where_sql 
        ORDER BY m.id DESC";

// Consulta para contar total
$sql_count = "SELECT COUNT(*) as total FROM margi m $where_sql";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params) && !empty($types)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_filtrados = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_filtrados / $registros_por_pagina);

// Agregar LIMIT y OFFSET
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$f_types = $types . "ii";
$f_params = array_merge($params, [$registros_por_pagina, $offset]);
if (!empty($f_params)) {
    $stmt->bind_param($f_types, ...$f_params);
}
$stmt->execute();
$res = $stmt->get_result();

// Declaración local protegida para evitar errores de duplicado
if (!function_exists('getDashboardColor')) {
    function getDashboardColor($estado) {
        if ($estado === 'CERRADO' || $estado === 'Activa') return 'success';
        if ($estado === 'EN_REVISION') return 'info';
        if ($estado === 'OBSERVADO') return 'danger';
        return 'primary'; // DIGITADO
    }
}
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
    
.pre-text-modal::-webkit-scrollbar {
    width: 8px;
}
.pre-text-modal::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.pre-text-modal::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}
.pre-text-modal::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
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
    
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card bg-white text-dark border-start border-5 border-primary" onclick="filtrarPorEstado('DIGITADO')">
                <div class="card-body">
                    <div class="stat-icon text-primary"><i class="bi bi-pencil-square"></i></div>
                    <p class="text-primary">Digitados</p>
                    <h3><?php echo number_format($c_dig); ?></h3>
                    <small class="text-muted">Pendientes de envío</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card bg-white text-dark border-start border-5 border-warning" onclick="filtrarPorEstado('OBSERVADO')">
                <div class="card-body">
                    <div class="stat-icon text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <p class="text-warning">Observados</p>
                    <h3><?php echo number_format($c_obs); ?></h3>
                    <small class="text-muted">Requieren corrección</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card bg-white text-dark border-start border-5 border-info" onclick="filtrarPorEstado('EN_REVISION')">
                <div class="card-body">
                    <div class="stat-icon text-info"><i class="bi bi-send-check-fill"></i></div>
                    <p class="text-info">En Revisión</p>
                    <h3><?php echo number_format($c_rev); ?></h3>
                    <small class="text-muted">En control de calidad</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card stat-card bg-white text-dark border-start border-5 border-success" onclick="filtrarPorEstado('CERRADO')">
                <div class="card-body">
                    <div class="stat-icon text-success"><i class="bi bi-check-circle-fill"></i></div>
                    <p class="text-success">Aprobados</p>
                    <h3><?php echo number_format($c_cer); ?></h3>
                    <small class="text-muted">Registros activos</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card card-filter mb-4 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" id="form-filtros">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="filter-label"><i class="bi bi-search"></i> Búsqueda General</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" 
                                   placeholder="ID Digital, Partida, Libro, Trámite o Texto..." 
                                   value="<?php echo htmlspecialchars($q); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Desde</label>
                        <input type="date" name="f_inicio" class="form-control" value="<?php echo $f_inicio; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="filter-label">Hasta</label>
                        <input type="date" name="f_fin" class="form-control" value="<?php echo $f_fin; ?>">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <div class="form-check form-switch me-3 mt-auto">
                                <input class="form-check-input" type="checkbox" name="ver_todos" id="verTodos" 
                                       <?php echo $ver_todos ? 'checked' : ''; ?>>
                                <label class="form-check-label small fw-bold" for="verTodos">Ver todos los usuarios</label>
                            </div>
                            <button type="submit" class="btn btn-dark px-4">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-eraser-fill"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="estado" id="filtro_estado" value="<?php echo htmlspecialchars($estado_filtro); ?>">
            </form>
        </div>
    </div>
    
    <?php if ($res && $res->num_rows > 0): ?>
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="badge bg-secondary px-3 py-2">
                <i class="bi bi-files"></i> <?php echo $total_filtrados; ?> registro(s) encontrado(s)
            </span>
            <?php if (!empty($q)): ?>
                <span class="badge bg-info px-3 py-2 ms-2">
                    <i class="bi bi-search"></i> Buscando: "<?php echo htmlspecialchars($q); ?>"
                </span>
            <?php endif; ?>
            <?php if (!empty($estado_filtro)): ?>
                <span class="badge bg-secondary px-3 py-2 ms-2">
                    <i class="bi bi-tag"></i> Estado: <?php echo $estado_filtro; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($total_paginas > 1): ?>
        <div class="text-muted small">
            Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php while ($row = $res->fetch_assoc()):
        $id_dig = ($row['LibroO'] >= 2026) ? $row['LibroO']."-".$row['NMargi1'] : $row['busquedalf'];
        $estado_badge = getDashboardColor($row['estado']); 
        $digitador = !empty($row['usuario_creo']) ? $row['usuario_creo'] : ($row['Iniciales1'] ?? 'N/A');
        $obs_qc = trim($row['observaciones_qc'] ?? '');
        $puede_editar = (in_array($row['estado'], ['DIGITADO', 'OBSERVADO']) || in_array($rol_usuario, ['ADMINISTRADOR', 'SUPERVISOR']));
        $puede_enviar = (in_array($row['estado'], ['DIGITADO', 'OBSERVADO']));
        
        // Formatear fecha
        $fecha_creacion = date('d/m/Y H:i', strtotime($row['FechaC']));
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
                <div class="btn-group btn-group-sm mt-2 mt-sm-0">
                   <button type="button" class="btn btn-outline-primary btn-sm" title="Ver Detalle" onclick="abrirModal(<?php echo $row['id']; ?>)">
    <i class="bi bi-eye"></i> Ver
</button>
                    <?php if($puede_editar): ?>
                        <a href="editar.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-warning" title="Editar">
                            <i class="bi bi-pencil-square"></i> Editar
                        </a>
                    <?php endif; ?>
                    <?php if($puede_enviar): ?>
                        <button onclick="enviarARevision(<?php echo $row['id']; ?>)" class="btn btn-outline-info" title="Enviar a Control de Calidad">
                            <i class="bi bi-send-check"></i> Enviar
                        </button>
                    <?php endif; ?>
                    <a href="../reports/generar_pdf.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-outline-dark" title="Ver PDF">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
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
                        
                        <?php if($obs_qc): ?>
                        <div class="alert alert-danger alert-sm p-2 mt-3 small">
                            <i class="bi bi-chat-dots-fill"></i>
                            <strong>Observaciones QC:</strong><br>
                            <?php echo nl2br(htmlspecialchars(substr($obs_qc, 0, 150))); ?>
                            <?php if(strlen($obs_qc) > 150): ?>...<?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-9">
                        <p class="filter-label mb-2"><i class="bi bi-text-paragraph"></i> Texto de la Marginación</p>
                        <div class="pre-text">
                            <?php echo resaltar($row['TxtMargi1'], $q); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    
    <?php if ($total_paginas > 1): ?>
    <nav class="mt-4 mb-5">
        <ul class="pagination justify-content-center">
            <?php if ($pagina_actual > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            
            <?php 
            $inicio_pag = max(1, $pagina_actual - 2);
            $fin_pag = min($total_paginas, $pagina_actual + 2);
            for ($i = $inicio_pag; $i <= $fin_pag; $i++): 
            ?>
                <li class="page-item <?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox-fill fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No se encontraron registros</h5>
            <p class="text-muted small">
                <?php if (!empty($q) || !empty($f_inicio) || $ver_todos || !empty($estado_filtro)): ?>
                    Intente con otros criterios de búsqueda o <a href="dashboard.php">limpie los filtros</a>.
                <?php else: ?>
                    No hay registros en estado DIGITADO. <a href="nueva.php">Cree una nueva marginación</a>.
                <?php endif; ?>
            </p>
            <?php if (empty($q) && empty($f_inicio) && !$ver_todos && empty($estado_filtro)): ?>
            <a href="nueva.php" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle"></i> Nueva Marginación
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function abrirModal(id) {
    const modalElement = document.getElementById('verModal');
    const modalContent = document.getElementById('modalContent');
    
    // Mostrar loader
    modalContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando información...</p>
        </div>
    `;
    
    // Abrir modal
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Cargar contenido vía AJAX
    fetch(`ver_marginacion_modal.php?id=${id}`)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    Error al cargar el contenido. Intente nuevamente.
                </div>
            `;
            console.error('Error:', error);
        });
}
function enviarARevision(id) {
    if (!confirm('¿Enviar esta marginación a Control de Calidad?')) return;
    
    $.ajax({
        url: '../actions/enviar_revision.php',
        method: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(r) { 
            if (r.status === 'success') {
                location.reload(); 
            } else {
                alert('Error: ' + (r.message || 'No se pudo enviar el registro'));
            }
        },
        error: function() {
            alert('Error en la comunicación con el servidor.');
        }
    });
}

function filtrarPorEstado(estado) {
    document.getElementById('filtro_estado').value = estado;
    document.getElementById('form-filtros').submit();
}

// Mostrar mensaje de éxito si viene del guardado
<?php if(isset($_GET['msj'])): ?>
$(document).ready(function() {
    let mensaje = '';
    switch('<?php echo $_GET['msj']; ?>') {
        case 'guardado':
            mensaje = '✅ Marginación guardada exitosamente.';
            break;
        case 'actualizado':
            mensaje = '✏️ Registro actualizado correctamente.';
            break;
        case 'enviado':
            mensaje = '📤 Registro enviado a Control de Calidad.';
            break;
        case 'combo_exitoso':
            mensaje = '🎉 Se generaron <?php echo $_GET['total'] ?? 0; ?> marginaciones exitosamente.';
            break;
        default:
            mensaje = 'Operación completada con éxito.';
    }
    
    const toast = $('<div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow" style="z-index: 9999; min-width: 250px;">' +
        '<i class="bi bi-check-circle-fill me-2"></i> ' + mensaje +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    $('body').append(toast);
    setTimeout(function() { toast.alert('close'); }, 5000);
});
<?php endif; ?>

<?php if(isset($_GET['error'])): ?>
$(document).ready(function() {
    let mensaje = '';
    switch('<?php echo $_GET['error']; ?>') {
        case 'solo_marginadores':
            mensaje = '⚠️ Acceso denegado. Solo marginadores pueden acceder.';
            break;
        case 'sin_permiso':
            mensaje = '⚠️ No tiene permisos para realizar esta acción.';
            break;
        default:
            mensaje = '❌ Ocurrió un error. Intente nuevamente.';
    }
    
    const toast = $('<div class="alert alert-danger alert-dismissible fade show position-fixed bottom-0 end-0 m-3 shadow" style="z-index: 9999; min-width: 250px;">' +
        '<i class="bi bi-exclamation-triangle-fill me-2"></i> ' + mensaje +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    $('body').append(toast);
    setTimeout(function() { toast.alert('close'); }, 5000);
});
<?php endif; ?>
</script>

<!-- Modal global para visualización rápida -->
<div class="modal fade" id="verModal" tabindex="-1" aria-labelledby="verModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title" id="verModalLabel">
                    <i class="bi bi-eye-fill"></i> Vista previa de Marginación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <div id="modalContent" class="p-3">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando información...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?>