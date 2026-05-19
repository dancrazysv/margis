<?php
require_once '../config/db.php';
require_once '../core/Plantillas.php';
require_once '../core/Auth.php';
include '../templates/header.php';

// 1. Validar que exista un ID numérico
$id_tabla = $_GET['id'] ?? null;

if (!$id_tabla) {
    die("<div class='container mt-4 alert alert-danger'>Error: Identificador de registro no proporcionado.</div>");
}

// 2. Cargar el registro por su llave primaria
$stmt = $conn->prepare("SELECT * FROM margi WHERE id = ?");
$stmt->bind_param("i", $id_tabla);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    die("<div class='container mt-4 alert alert-danger'>Error: Registro no encontrado en la base de datos.</div>");
}

// Determinar el ID Digital para mostrar
$id_display = ($registro['LibroO'] >= 2026) 
    ? $registro['LibroO'] . "-" . $registro['NMargi1'] 
    : $registro['busquedalf'];

// 3. Verificar permisos de edición
$tipo_usuario = $_SESSION['user_tipo'] ?? 'DIGITADOR'; 

if ($registro['estado'] === 'CERRADO' && $tipo_usuario !== 'ADMIN') {
    echo "<div class='container mt-4 alert alert-warning'>
            <h4>Acceso Restringido</h4>
            <p>Este registro tiene estado <strong>CERRADO</strong> y no puede ser modificado.</p>
            <a href='dashboard.php' class='btn btn-primary'>Regresar al Dashboard</a>
          </div>";
    include '../templates/footer.php';
    exit;
}
?>

<div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editando Registro: <?php echo $id_display; ?></h5>
            <span class="badge bg-dark">Estado: <?php echo $registro['estado']; ?></span>
        </div>
        <div class="card-body">
            
            <?php if (!empty($registro['observaciones_qc'])): ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill"></i> <strong>Observaciones de Control de Calidad:</strong><br>
                    <?php echo htmlspecialchars($registro['observaciones_qc']); ?>
                </div>
            <?php endif; ?>

            <form action="../actions/actualizar.php" method="POST">
                <input type="hidden" name="id_registro" value="<?php echo $registro['id']; ?>">

                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                    <div class="col-md-5">
                        <label class="form-label fw-bold text-danger"><i class="bi bi-hash"></i> Número de Trámite (Expediente):</label>
                        <input type="text" name="tramite_num" class="form-control form-control-lg fw-bold" 
                               value="<?php echo htmlspecialchars($registro['num_tramite'] ?? ''); ?>" 
                               required placeholder="Ej: 2026-8520-6">
                        <div class="form-text">Debe incluir el año y correlativo completo.</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tipo de Partida</label>
                        <select name="tipo_p" class="form-select border-primary" required>
                            <?php 
                            $res_t = $conn->query("SELECT nombre_partida FROM tipo_partida WHERE grupo_partida = 1 ORDER BY nombre_partida ASC");
                            while($t = $res_t->fetch_assoc()): 
                                $selected = ($t['nombre_partida'] == $registro['TipoP']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $t['nombre_partida']; ?>" <?php echo $selected; ?>>
                                    <?php echo $t['nombre_partida']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Número de Partida</label>
                        <input type="text" name="partida" class="form-control" value="<?php echo $registro['NPartida']; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Libro</label>
                        <input type="text" name="libro_p" class="form-control" value="<?php echo $registro['LibroP']; ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">Folio</label>
                        <input type="text" name="folio" class="form-control" value="<?php echo $registro['FolioO']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Tomo (Opcional)</label>
                        <input type="text" name="tomo" class="form-control border-info" value="<?php echo $registro['TomoP'] ?? ''; ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-primary">Distrito de la Partida</label>
                        <select name="distrito_partida" class="form-select border-primary">
                            <?php 
                            $distritos = ['San Salvador', 'Mejicanos', 'Ayutuxtepeque', 'Cuscatancingo', 'Ciudad Delgado'];
                            $valor_actual = $registro['lugar'] ?? $registro['distrito'] ?? ''; 
                            
                            foreach ($distritos as $d): 
                                $selected = (strtoupper($valor_actual) == strtoupper($d)) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $d; ?>" <?php echo $selected; ?>>
                                    <?php echo $d; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Cuerpo de la Marginación</label>
                    <textarea name="texto_final" class="form-control shadow-sm" rows="12" style="font-family: 'Courier New', monospace; font-size: 1rem;"><?php echo trim($registro['TxtMargi1']); ?></textarea>
                    <div class="form-text">Revise cuidadosamente la ortografía y puntuación antes de guardar.</div>
                </div>

                <div class="d-flex justify-content-between border-top pt-4 mt-4">
                    <a href="dashboard.php" class="btn btn-secondary px-4">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <div class="gap-2">
                        <button type="submit" name="accion" value="GUARDAR" class="btn btn-primary px-4 shadow-sm">
                            <i class="bi bi-save"></i> Guardar Cambios
                        </button>
                        <button type="submit" name="accion" value="ENVIAR" class="btn btn-success px-4 shadow-sm">
                            <i class="bi bi-send-check"></i> Guardar y Enviar a Revisión
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>