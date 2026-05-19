<?php 
require_once '../config/db.php'; 
include '../templates/header.php'; 

// 1. Recibimos el ID numérico de la tabla (Primary Key)
$id_tabla = $_GET['id'] ?? null;

if (!$id_tabla) {
    die("<div class='container mt-4 alert alert-danger'>Error: No se proporcionó un identificador válido.</div>");
}

// 2. Consultamos directamente por la llave primaria 'id'
$stmt = $conn->prepare("SELECT * FROM margi WHERE id = ?");
$stmt->bind_param("i", $id_tabla);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    die("<div class='container mt-4 alert alert-danger'>Registro no encontrado en la base de datos.</div>");
}

// Determinamos el ID Digital para mostrar en pantalla
$id_digital = ($registro['LibroO'] >= 2026) 
    ? $registro['LibroO'] . "-" . $registro['NMargi1'] 
    : $registro['busquedalf'];

// Lógica de Usuario (Digitador)
$creador = !empty($registro['usuario_creo']) ? $registro['usuario_creo'] : ($registro['Iniciales1'] ?? 'N/A');

// Lógica de Fecha y Hora
$fecha_completa = $registro['FechaC'];
$solo_fecha = date("d/m/Y", strtotime($fecha_completa));
$solo_hora  = date("h:i:s A", strtotime($fecha_completa));
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Visualizando Asiento: <?php echo htmlspecialchars($id_digital); ?></h5>
                <span class="badge bg-warning text-dark mt-1">Trámite: <?php echo htmlspecialchars($registro['num_tramite'] ?? 'N/A'); ?></span>
            </div>
            <span class="badge bg-light text-dark">Estado: <?php echo $registro['estado']; ?></span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-8">
                    <h6>Texto de la Marginación:</h6>
                    <div class="p-4 bg-light border rounded shadow-sm" style="font-family: 'Courier New', Courier, monospace;  line-height: 1.6; font-size: 1.05rem;">
                        <?php echo htmlspecialchars($registro['TxtMargi1']); ?>
                    </div>
                </div>
                
                <div class="col-md-4 border-start">
                    <h6 class="text-primary border-bottom pb-2">Referencias de Partida:</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 text-danger"><strong><i class="bi bi-hash"></i> Número de Trámite:</strong> <?php echo htmlspecialchars($registro['num_tramite'] ?? 'N/A'); ?></li>
                        <hr class="my-2">
                        
                        <li class="mb-1"><strong>Tipo:</strong> <?php echo $registro['TipoP']; ?></li>
                        <li class="mb-1"><strong>Partida:</strong> <?php echo $registro['NPartida']; ?></li>
                        <li class="mb-1"><strong>Libro:</strong> <?php echo $registro['LibroP']; ?></li>
                        <li class="mb-1"><strong>Folio:</strong> <?php echo $registro['FolioO']; ?></li>
                        
                        <?php if(!empty($registro['TomoP'])): ?>
                            <li class="mb-1"><strong>Tomo:</strong> <?php echo $registro['TomoP']; ?></li>
                        <?php endif; ?>
                        
                        <li class="mb-1"><strong>Año Part.:</strong> <?php echo $registro['AnioP']; ?></li>
                        <li class="mb-1"><strong>Lugar:</strong> <?php echo $registro['lugar']; ?></li>
                        
                        <hr class="my-2">
                        
                        <li class="mb-1"><strong>Digitó:</strong> <?php echo htmlspecialchars($creador); ?></li>
                        <li class="mb-1"><strong>Distrito:</strong> <?php echo htmlspecialchars($registro['lugar']); ?></li>
                    </ul>

                    <div class="mt-4 pt-2 border-top">
                        <h6 class="text-secondary small fw-bold text-uppercase">Registro del Sistema</h6>
                        <small class="d-block text-muted">Fecha: <?php echo $solo_fecha; ?></small>
                        <small class="d-block text-muted">Hora: <?php echo $solo_hora; ?></small>
                    </div>
                </div>
            </div>

            <?php if ($registro['estado'] !== 'CERRADO' && isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'CALIDAD'): ?>
            <div class="alert alert-info border-0 shadow-sm mt-4">
                <h6 class="fw-bold"><i class="bi bi-shield-check"></i> Panel de Control de Calidad</h6>
                <form action="../actions/validar.php" method="POST" class="row g-2">
                    <input type="hidden" name="id_registro" value="<?php echo $registro['id']; ?>">
                    <div class="col-md-8">
                        <input type="text" name="observaciones" class="form-control" placeholder="Observaciones si se devuelve...">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button name="accion" value="APROBAR" class="btn btn-success w-100">Aprobar y Cerrar</button>
                        <button name="accion" value="RECHAZAR" class="btn btn-danger w-100">Devolver</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer d-flex justify-content-between bg-white py-3">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Regresar
            </a>
            <div class="btn-group">
                <a href="../reports/generar_pdf.php?id=<?php echo $registro['id']; ?>" target="_blank" class="btn btn-dark">
                    <i class="bi bi-printer"></i> Ver PDF
                </a>
                <?php if ($registro['estado'] !== 'CERRADO'): ?>
                <a href="editar.php?id=<?php echo $registro['id']; ?>" class="btn btn-warning text-dark fw-bold">
                    <i class="bi bi-pencil-square"></i> Editar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>