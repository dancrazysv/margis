<?php
// public/confirmar_duplicado.php
require_once '../config/db.php';
require_once '../core/plantillas.php';
include '../templates/header.php';

if (!isset($_SESSION['duplicados_encontrados']) || empty($_SESSION['duplicados_encontrados'])) {
    header("Location: dashboard.php");
    exit;
}

$duplicados = $_SESSION['duplicados_encontrados'];
$datos_pendientes = $_SESSION['datos_pendientes'];
$fecha_evento = $_SESSION['fecha_evento'];
$iniciales_partida = $_SESSION['iniciales_partida'];
?>

<div class="container mt-4">
    <div class="card border-danger shadow">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Posible Marginación Duplicada</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Se encontraron marginaciones existentes con los mismos datos clave:</strong>
                <ul class="mt-2 mb-0">
                    <li><strong>Partida N°:</strong> <?php echo htmlspecialchars($datos_pendientes['n_partida_1'] ?? ''); ?></li>
                    <li><strong>Año Digital:</strong> <?php echo htmlspecialchars($datos_pendientes['anio_digital'] ?? ''); ?></li>
                    <li><strong>Fecha del Evento:</strong> <?php echo htmlspecialchars($fecha_evento); ?></li>
                    <li><strong>Tipo de Partida:</strong> <?php echo htmlspecialchars($iniciales_partida); ?></li>
                </ul>
            </div>
            
            <h6 class="fw-bold mt-3">Marginaciones existentes:</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Digital</th>
                            <th>Texto de Marginación</th>
                            <th>Fecha de Creación</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duplicados as $dup): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($dup['libro_nmargi_concat']); ?></code></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($dup['TxtMargi1'], 0, 150) . '...')); ?></td>
                            <td><?php echo htmlspecialchars($dup['FechaC']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($dup['estado']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-danger mt-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>¿Desea continuar guardando esta marginación?</strong> Si continúa, se creará un duplicado.
            </div>
            
            <div class="d-flex justify-content-end gap-3 mt-4">
                <form action="guardar_dinamico.php" method="POST" id="form-continuar">
                    <?php foreach ($datos_pendientes as $key => $value): ?>
                        <?php if (is_array($value)): ?>
                            <?php foreach ($value as $k => $v): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>[<?php echo htmlspecialchars($k); ?>]" value="<?php echo htmlspecialchars($v); ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <input type="hidden" name="confirmar_duplicado" value="1">
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-check-circle"></i> Continuar (Guardar de todos modos)
                    </button>
                    <a href="nueva.php?p=<?php echo $datos_pendientes['plantilla_id'] ?? ''; ?>" class="btn btn-secondary btn-lg">
                        <i class="bi bi-arrow-left"></i> Regresar y corregir
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>