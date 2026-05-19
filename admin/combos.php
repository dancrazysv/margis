<?php
// admin/combos.php - Gestor de Combos (Actos Múltiples)
require_once '../config/db.php';
require_once '../core/plantillas.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit;
}

if ($_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: ../public/dashboard.php?error=sin_permiso");
    exit;
}

include '../templates/header.php';

$accion = $_GET['accion'] ?? 'listar';
$combo_id = (int)($_GET['id'] ?? 0);
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-collection-fill"></i> Gestor de Combos (Actos Múltiples)</h5>
            <?php if ($accion == 'listar'): ?>
                <a href="?accion=nuevo" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle"></i> Nuevo Combo
                </a>
            <?php else: ?>
                <a href="?accion=listar" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            
            <?php if ($accion == 'listar'): ?>
                <!-- LISTADO DE COMBOS CON BUSCADOR EN TIEMPO REAL -->
                <?php
                if (isset($_GET['eliminado']) && $_GET['eliminado'] == 1):
                ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Combo eliminado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- BUSCADOR EN TIEMPO REAL -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="input-group" style="max-width: 350px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="filtroCombos" class="form-control" placeholder="Buscar combo (nombre, descripción, plantillas...)" autocomplete="off">
                        <button class="btn btn-outline-secondary" id="limpiarFiltro" type="button"><i class="bi bi-eraser"></i></button>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-info-circle"></i> Escriba para filtrar en tiempo real
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaCombos">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Combo</th>
                                <th>Descripción</th>
                                <th>Plantillas</th>
                                <th>Orden</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * FROM combos_marginaciones ORDER BY orden";
                            $result = $conn->query($sql);
                            if ($result->num_rows == 0):
                            ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="bi bi-info-circle"></i> No hay combos registrados. 
                                    <a href="?accion=nuevo">Crea tu primer combo</a>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php while($row = $result->fetch_assoc()):
                                    $stmt = $conn->prepare("SELECT COUNT(*) as total, GROUP_CONCAT(pt.nombre_tramite SEPARATOR ', ') as nombres 
                                                            FROM combo_plantillas cp 
                                                            JOIN plantillas_textos pt ON cp.plantilla_id = pt.id 
                                                            WHERE cp.combo_id = ?");
                                    $stmt->bind_param("i", $row['id']);
                                    $stmt->execute();
                                    $plantillas_info = $stmt->get_result()->fetch_assoc();
                                    $total_plantillas = $plantillas_info['total'] ?? 0;
                                    $nombres_plantillas = $plantillas_info['nombres'] ?? '';
                                ?>
                                <tr data-nombre="<?= htmlspecialchars(strtolower($row['nombre_combo'])) ?>"
                                    data-descripcion="<?= htmlspecialchars(strtolower($row['descripcion'] ?? '')) ?>"
                                    data-plantillas="<?= htmlspecialchars(strtolower($nombres_plantillas)) ?>">
                                    <td><?= $row['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($row['nombre_combo']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['descripcion'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $total_plantillas ?> marginaciones</span>
                                        <?php if($nombres_plantillas): ?>
                                            <div class="small text-muted mt-1" title="<?= htmlspecialchars($nombres_plantillas) ?>">
                                                <?= htmlspecialchars(substr($nombres_plantillas, 0, 50)) ?>
                                                <?= strlen($nombres_plantillas) > 50 ? '...' : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['orden'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['activo'] ? 'success' : 'danger' ?>">
                                            <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?accion=editar&id=<?= $row['id'] ?>" class="btn btn-warning" title="Editar Combo">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?accion=plantillas&id=<?= $row['id'] ?>" class="btn btn-info" title="Configurar Plantillas">
                                                <i class="bi bi-files"></i>
                                            </a>
                                            <a href="../public/combos.php?combo_id=<?= $row['id'] ?>" target="_blank" class="btn btn-success" title="Probar Combo">
                                                <i class="bi bi-play"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" title="Eliminar Combo" 
                                                    onclick="eliminarCombo(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nombre_combo'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($accion == 'nuevo' || $accion == 'editar'): 
                $combo = [
                    'id' => 0,
                    'nombre_combo' => '',
                    'descripcion' => '',
                    'activo' => 1,
                    'orden' => 0
                ];
                
                if ($combo_id > 0) {
                    $stmt = $conn->prepare("SELECT * FROM combos_marginaciones WHERE id = ?");
                    $stmt->bind_param("i", $combo_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $combo = $row;
                    }
                }
            ?>
                <!-- FORMULARIO DE COMBO -->
                <form method="POST" action="guardar_combo.php">
                    <input type="hidden" name="id" value="<?= $combo['id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Nombre del Combo *</label>
                            <input type="text" name="nombre_combo" class="form-control" 
                                   value="<?= htmlspecialchars($combo['nombre_combo']) ?>" required>
                            <div class="form-text">Ej: "Matrimonio Civil", "Divorcio", "Muerte de Cónyuge", "Rectificación de Nombre"</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Orden</label>
                            <input type="number" name="orden" class="form-control" value="<?= $combo['orden'] ?>">
                            <div class="form-text">Número menor = aparece primero</div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-bold">Activo</label>
                            <select name="activo" class="form-select">
                                <option value="1" <?= $combo['activo'] ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= !$combo['activo'] ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3" 
                                  placeholder="Describa qué marginaciones genera este combo y cuándo se usa"><?= htmlspecialchars($combo['descripcion'] ?? '') ?></textarea>
                        <div class="form-text">Esta descripción la verán los marginadores al seleccionar el combo.</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Nota:</strong> Después de guardar el combo, deberá asignar las plantillas que lo componen.
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Guardar Combo
                        </button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
                
            <?php elseif ($accion == 'plantillas'): 
                $stmt = $conn->prepare("SELECT * FROM combos_marginaciones WHERE id = ?");
                $stmt->bind_param("i", $combo_id);
                $stmt->execute();
                $combo = $stmt->get_result()->fetch_assoc();
                
                if (!$combo):
            ?>
                <div class="alert alert-danger">Combo no encontrado</div>
            <?php else: 
                $stmt = $conn->prepare("
                    SELECT cp.*, pt.nombre_tramite, pt.tipo_asiento, pt.tipo_acto
                    FROM combo_plantillas cp
                    JOIN plantillas_textos pt ON cp.plantilla_id = pt.id
                    WHERE cp.combo_id = ?
                    ORDER BY cp.orden
                ");
                $stmt->bind_param("i", $combo_id);
                $stmt->execute();
                $plantillas_asignadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                $plantillas_disponibles = $conn->query("SELECT id, nombre_tramite, tipo_asiento, tipo_acto FROM plantillas_textos WHERE activo = 1 ORDER BY nombre_tramite");
            ?>
                <h5 class="mb-3">Configurando plantillas para: <strong><?= htmlspecialchars($combo['nombre_combo']) ?></strong></h5>
                <p class="text-muted small">Seleccione las plantillas que se generarán al usar este combo. El orden determina el ID digital que recibirán.</p>
                
                <form method="POST" action="guardar_combo_plantillas.php">
                    <input type="hidden" name="combo_id" value="<?= $combo_id ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">Seleccionar</th>
                                    <th>Plantilla</th>
                                    <th>Tipo Asiento</th>
                                    <th>Tipo Acto</th>
                                    <th width="80">Orden</th>
                                    <th width="150">Requiere Partida Propia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $asignadas_ids = array_column($plantillas_asignadas, 'plantilla_id');
                                $orden_counter = 1;
                                if ($plantillas_disponibles->num_rows == 0):
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="bi bi-exclamation-triangle"></i> No hay plantillas disponibles. 
                                        <a href="plantillas.php">Cree plantillas primero</a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php while($pt = $plantillas_disponibles->fetch_assoc()): 
                                        $asignada = in_array($pt['id'], $asignadas_ids);
                                        $orden_actual = '';
                                        $requiere_partida = 1;
                                        
                                        if ($asignada) {
                                            foreach ($plantillas_asignadas as $pa) {
                                                if ($pa['plantilla_id'] == $pt['id']) {
                                                    $orden_actual = $pa['orden'];
                                                    $requiere_partida = $pa['requiere_partida_propia'];
                                                    break;
                                                }
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="plantillas[<?= $pt['id'] ?>][seleccionado]" 
                                                   value="1" <?= $asignada ? 'checked' : '' ?> 
                                                   class="checkbox-plantilla"
                                                   onchange="toggleFila(this, <?= $pt['id'] ?>)">
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($pt['nombre_tramite']) ?>
                                            <input type="hidden" name="plantillas[<?= $pt['id'] ?>][id]" value="<?= $pt['id'] ?>">
                                        </td>
                                        <td><span class="badge bg-info"><?= $pt['tipo_asiento'] ?></span></td>
                                        <td><span class="badge bg-secondary"><?= $pt['tipo_acto'] ?></span></td>
                                        <td>
                                            <input type="number" name="plantillas[<?= $pt['id'] ?>][orden]" 
                                                   class="form-control form-control-sm orden-input" style="width: 70px;" 
                                                   value="<?= $orden_actual ?: $orden_counter ?>"
                                                   <?= !$asignada ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <select name="plantillas[<?= $pt['id'] ?>][requiere_partida]" 
                                                    class="form-select form-select-sm requiere-partida-select"
                                                    <?= !$asignada ? 'disabled' : '' ?>>
                                                <option value="1" <?= $requiere_partida == 1 ? 'selected' : '' ?>>Sí - Requiere su propia partida</option>
                                                <option value="0" <?= $requiere_partida == 0 ? 'selected' : '' ?>>No - Usa partida de referencia</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php 
                                    $orden_counter++;
                                    endwhile; 
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>Nota:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Las plantillas seleccionadas se generarán en el orden indicado.</li>
                            <li>Cada marginación recibirá un ID digital correlativo automático.</li>
                            <li><strong>"Requiere Partida Propia"</strong> = Sí: la marginación necesita su propio número de partida en el formulario.</li>
                        </ul>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Guardar Plantillas del Combo
                        </button>
                        <a href="?accion=listar" class="btn btn-secondary">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el combo <strong id="combo_nombre_eliminar"></strong>?</p>
                <p class="text-danger small">Esta acción eliminará también la relación con sus plantillas. Las plantillas NO se eliminan, solo se desasocian del combo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Sí, Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
// ================== POLYFILL PARA SELECT2 ==================
if (typeof $.fn.select2 === 'undefined') {
    $.fn.select2 = function() { return this; };
}

let comboIdAEliminar = null;

function eliminarCombo(id, nombre) {
    comboIdAEliminar = id;
    document.getElementById('combo_nombre_eliminar').innerText = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

document.getElementById('btnConfirmarEliminar')?.addEventListener('click', function() {
    if (!comboIdAEliminar) return;
    
    fetch('eliminar_combo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + comboIdAEliminar
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = '?accion=listar&eliminado=1';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error al eliminar el combo');
    });
});

function toggleFila(checkbox, id) {
    const fila = checkbox.closest('tr');
    const ordenInput = fila.querySelector('.orden-input');
    const partidaSelect = fila.querySelector('.requiere-partida-select');
    
    if (checkbox.checked) {
        ordenInput.disabled = false;
        partidaSelect.disabled = false;
        if (!ordenInput.value) {
            let maxOrden = 0;
            document.querySelectorAll('.orden-input:not([disabled])').forEach(input => {
                let val = parseInt(input.value);
                if (!isNaN(val) && val > maxOrden) maxOrden = val;
            });
            ordenInput.value = maxOrden + 1;
        }
    } else {
        ordenInput.disabled = true;
        partidaSelect.disabled = true;
        ordenInput.value = '';
    }
}

document.addEventListener('change', function(e) {
    if (e.target.classList && e.target.classList.contains('orden-input')) {
        let ordenes = [];
        document.querySelectorAll('.orden-input:not([disabled])').forEach(input => {
            ordenes.push({
                input: input,
                valor: parseInt(input.value) || 0
            });
        });
        
        ordenes.sort((a, b) => a.valor - b.valor);
        
        let nuevoOrden = 1;
        ordenes.forEach(item => {
            if (item.valor !== nuevoOrden) {
                item.input.value = nuevoOrden;
            }
            nuevoOrden++;
        });
    }
});

// ================== FILTRO EN TIEMPO REAL PARA LA TABLA DE COMBOS ==================
if (document.getElementById('filtroCombos')) {
    const $filtro = $('#filtroCombos');
    const $limpiar = $('#limpiarFiltro');
    const $filas = $('#tablaCombos tbody tr');

    function filtrarCombos() {
        const term = $filtro.val().toLowerCase().trim();
        $filas.each(function() {
            const $fila = $(this);
            // Obtener texto de las columnas que queremos buscar: nombre, descripción y lista de plantillas
            const nombre = $fila.find('td:eq(1)').text().toLowerCase();
            const descripcion = $fila.find('td:eq(2)').text().toLowerCase();
            const plantillas = $fila.find('td:eq(3)').text().toLowerCase();
            
            const coincide = nombre.includes(term) || descripcion.includes(term) || plantillas.includes(term);
            $fila.toggle(coincide);
        });
    }

    $filtro.on('input', filtrarCombos);
    $limpiar.on('click', function() {
        $filtro.val('').trigger('input');
        $filtro.focus();
    });
}
</script>

<?php include '../templates/footer.php'; ?>