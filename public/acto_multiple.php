<?php
// public/acto_multiple.php
require_once '../config/db.php';
include '../templates/header.php';

if ($_SESSION['user_area'] !== 'MARGINADOR' && $_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: dashboard.php?error=solo_marginadores");
    exit;
}

$grupo = $_GET['grupo'] ?? '';
$anio_actual = ANIO_VIGENTE;

// Obtener grupos disponibles
$grupos_sql = "SELECT DISTINCT grupo_multiple FROM plantillas_textos WHERE grupo_multiple IS NOT NULL AND activo = 1";
$grupos_result = $conn->query($grupos_sql);
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-layers-fill"></i> Acto Múltiple - Generar varias marginaciones</h5>
        </div>
        <div class="card-body">
            
            <!-- Paso 1: Seleccionar el tipo de acto múltiple -->
            <?php if (empty($grupo)): ?>
                <h6 class="fw-bold mb-3">Seleccione el tipo de acto a generar:</h6>
                <div class="row">
                    <?php while($g = $grupos_result->fetch_assoc()): 
                        $nombre_grupo = str_replace('_', ' ', $g['grupo_multiple']);
                    ?>
                    <div class="col-md-4 mb-3">
                        <a href="?grupo=<?= urlencode($g['grupo_multiple']) ?>" class="text-decoration-none">
                            <div class="card border-primary h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-file-earmark-text fs-1 text-primary"></i>
                                    <h6 class="mt-2"><?= htmlspecialchars($nombre_grupo) ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: 
                // Obtener plantillas del grupo
                $stmt = $conn->prepare("SELECT id, nombre_tramite, cuerpo_legal, requiere_conyuge, requiere_leyenda 
                                        FROM plantillas_textos 
                                        WHERE grupo_multiple = ? AND activo = 1 
                                        ORDER BY id");
                $stmt->bind_param("s", $grupo);
                $stmt->execute();
                $plantillas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Verificar si es un grupo de matrimonio (requiere datos de ambos)
                $es_matrimonio = (strpos($grupo, 'MATRIMONIO') !== false);
            ?>
            
            <form method="POST" action="../actions/guardar_acto_multiple.php">
                <input type="hidden" name="grupo" value="<?= htmlspecialchars($grupo) ?>">
                
                <!-- ID Digital sugerido -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Se generarán las siguientes marginaciones:
                    <ul class="mb-0 mt-2">
                        <?php foreach($plantillas as $p): ?>
                        <li><?= htmlspecialchars($p['nombre_tramite']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Datos de foliación física (comunes para todas) -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">Datos de Foliación Física (Comunes)</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="small fw-bold">Tipo Partida</label>
                                <select name="tipo_p" class="form-select select2-init" required>
                                    <?php 
                                    $tps = $conn->query("SELECT nombre_partida FROM tipo_partida WHERE grupo_partida = 1 ORDER BY nombre_partida ASC"); 
                                    while($t = $tps->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $t['nombre_partida'] ?>"><?= $t['nombre_partida'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Distrito/Municipio</label>
                                <select name="lugar" class="form-select select2-init" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php 
                                    $muns = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                                    while($m = $muns->fetch_assoc()): 
                                    ?>
                                        <option value="<?= htmlspecialchars($m['municipio']) ?>"><?= htmlspecialchars($m['municipio']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><label class="small fw-bold">Año</label><input type="text" name="anio_p" class="form-control" required></div>
                            <div class="col-md-2"><label class="small fw-bold">Libro</label><input type="text" name="libro_p" class="form-control" required></div>
                            <div class="col-md-2"><label class="small fw-bold">Folio</label><input type="text" name="folio_o" class="form-control" required></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($es_matrimonio): ?>
                    <!-- Datos de ambos cónyuges -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">Datos de los Cónyuges</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="fw-bold">Partida de NACIMIENTO de ÉL</label>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12"><input type="text" name="n_partida_el" class="form-control" placeholder="Número de Partida" required></div>
                                        <div class="col-md-12"><input type="text" name="nombre_el" class="form-control" placeholder="Nombre Completo" required></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Partida de NACIMIENTO de ELLA</label>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-12"><input type="text" name="n_partida_ella" class="form-control" placeholder="Número de Partida" required></div>
                                        <div class="col-md-12"><input type="text" name="nombre_ella" class="form-control" placeholder="Nombre Completo" required></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="fw-bold">Datos del Matrimonio</label>
                                    <input type="text" name="lugar_boda" class="form-control mb-2" placeholder="Lugar de la boda" required>
                                    <input type="date" name="fecha_boda" class="form-control mb-2" placeholder="Fecha de boda" required>
                                    <select name="cargo_funcionario" class="form-select select2-init mb-2">
                                        <option value="">-- Cargo del Funcionario --</option>
                                        <?php 
                                        $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                                        while($c = $cargos->fetch_assoc()): 
                                        ?>
                                            <option value="<?= htmlspecialchars($c['cargo']) ?>"><?= htmlspecialchars($c['cargo']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <select name="nombre_funcionario" class="form-select select2-init">
                                        <option value="">-- Nombre del Funcionario --</option>
                                        <?php 
                                        $notarios = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                                        while($n = $notarios->fetch_assoc()): 
                                        ?>
                                            <option value="<?= htmlspecialchars($n['nombre']) ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Referencia Legal</label>
                                    <select class="form-select select2-init mb-2" id="tipo_ref">
                                        <option value="">-- Tipo de Documento --</option>
                                        <option value="escritura">Escritura Pública</option>
                                        <option value="acta">Acta Matrimonial</option>
                                    </select>
                                    <input type="text" id="ref_numero" class="form-control" placeholder="Número de documento">
                                    <input type="hidden" name="referencia_legal" id="referencia_final">
                                    
                                    <div class="mt-3">
                                        <label class="fw-bold">Leyenda de Apellidos (para ELLA)</label>
                                        <select name="leyenda_tipo" class="form-select select2-init">
                                            <option value="soltera">Seguirá usando sus apellidos de soltera</option>
                                            <option value="con_de">Usará "DE" + apellido del cónyuge</option>
                                            <option value="sin_de">Usará apellido del cónyuge sin "DE"</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Para actos no matrimoniales, datos específicos -->
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">Datos Generales</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Número de Partida a marginar</label>
                                    <input type="text" name="n_partida" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Nombre del inscrito</label>
                                    <input type="text" name="nombre_inscrito" class="form-control" required>
                                </div>
                            </div>
                            <!-- Aquí irían campos dinámicos según el grupo -->
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Datos comunes del trámite -->
                <div class="card mb-3">
                    <div class="card-header bg-warning text-dark">Control de Expediente</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="fw-bold">Número de Trámite</label>
                                <div class="input-group">
                                    <input type="text" name="tramite_anio" class="form-control" style="max-width: 80px;" value="<?= $anio_actual ?>">
                                    <span class="input-group-text">-</span>
                                    <input type="text" name="tramite_correlativo" class="form-control" placeholder="8520-6" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end">
                    <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-layers-fill"></i> Generar <?= count($plantillas) ?> Marginaciones
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2-init').select2({ theme: 'bootstrap-5', width: '100%' });
    
    $('#tipo_ref, #ref_numero').on('change input', function() {
        let tipo = $('#tipo_ref').val();
        let numero = $('#ref_numero').val();
        let referencia = '';
        if (tipo === 'escritura') {
            referencia = 'testimonio de escritura pública número ' + numero;
        } else if (tipo === 'acta') {
            referencia = 'certificación de acta matrimonial número ' + numero;
        }
        $('#referencia_final').val(referencia);
    });
});
</script>

<?php include '../templates/footer.php'; ?>