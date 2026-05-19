<?php
require_once '../config/db.php'; 
// No es necesario session_start() porque db.php ya la inicia

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo '<div class="alert alert-danger">ID no válido</div>';
    exit;
}

$stmt = $conn->prepare("
    SELECT m.*, u.distrito as distrito_usuario 
    FROM margi m 
    LEFT JOIN usuarios u ON (m.usuario_creo = u.iniciales OR m.Iniciales1 = u.iniciales)
    WHERE m.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo '<div class="alert alert-warning">Registro no encontrado</div>';
    exit;
}

$id_dig = ($row['LibroO'] >= 2026) ? $row['LibroO'] . "-" . $row['NMargi1'] : $row['busquedalf'];
$fecha_creacion = date('d/m/Y H:i', strtotime($row['FechaC']));
$obs_qc = trim($row['observaciones_qc'] ?? '');
?>

<div class="container-fluid p-3">
    <!-- Cabecera del contenido -->
    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
        <div>
            <h4 class="mb-1">Marginación: <?php echo htmlspecialchars($id_dig); ?></h4>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['estado']); ?></span>
            <span class="badge bg-info text-dark ms-1">Trámite: <?php echo htmlspecialchars($row['num_tramite'] ?? 'N/A'); ?></span>
        </div>
        <small class="text-muted">
            <i class="bi bi-clock"></i> <?php echo $fecha_creacion; ?>
        </small>
    </div>

    <div class="row g-3">
        <!-- Columna izquierda: datos clave -->
        <div class="col-md-4">
            <div class="card h-100 border-0 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold"><i class="bi bi-info-circle"></i> Datos de la partida</h6>
                    <hr class="my-2">
                    <ul class="list-unstyled small">
                        <li><strong>Tipo Partida:</strong> <?php echo htmlspecialchars($row['TipoP'] ?? ''); ?></li>
                        <li><strong>Partida N°:</strong> <?php echo htmlspecialchars($row['NPartida'] ?? ''); ?></li>
                        <li><strong>Libro:</strong> <?php echo htmlspecialchars($row['LibroP'] ?? ''); ?></li>
                        <li><strong>Folio:</strong> <?php echo htmlspecialchars($row['FolioO'] ?? ''); ?></li>
                        <?php if(!empty($row['TomoP'])): ?>
                            <li><strong>Tomo:</strong> <?php echo htmlspecialchars($row['TomoP']); ?></li>
                        <?php endif; ?>
                        <li><strong>Año Partida:</strong> <?php echo htmlspecialchars($row['AnioP'] ?? ''); ?></li>
                        <li><strong>Lugar:</strong> <?php echo htmlspecialchars($row['lugar'] ?? ''); ?></li>
                    </ul>

                    <h6 class="fw-bold mt-3"><i class="bi bi-person-badge"></i> Digitador</h6>
                    <hr class="my-2">
                    <p class="small mb-0">
                        <strong>Usuario:</strong> <?php echo htmlspecialchars($row['usuario_creo'] ?? $row['Iniciales1'] ?? 'N/A'); ?><br>
                        <strong>Distrito:</strong> <?php echo htmlspecialchars($row['distrito_usuario'] ?? 'N/A'); ?>
                    </p>

                    <?php if ($obs_qc): ?>
                        <div class="alert alert-danger mt-3 p-2 small">
                            <i class="bi bi-chat-dots-fill"></i>
                            <strong>Observaciones QC:</strong><br>
                            <?php echo nl2br(htmlspecialchars($obs_qc)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna derecha: texto completo -->
        <div class="col-md-8">
            <div class="card h-100 border-0">
                <div class="card-body">
                    <h6 class="fw-bold"><i class="bi bi-text-paragraph"></i> Texto completo</h6>
                    <hr>
                    <div class="pre-text-modal" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 12px; font-family: 'Courier New', monospace; line-height: 1.5;">
                        <?php echo nl2br(htmlspecialchars($row['TxtMargi1'] ?? '')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de acción dentro del modal -->
    <div class="mt-4 text-end border-top pt-3">
        <a href="../reports/generar_pdf.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-dark btn-sm me-2">
            <i class="bi bi-file-pdf"></i> Ver PDF
        </a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cerrar
        </button>
    </div>
</div>