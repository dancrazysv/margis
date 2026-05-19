<?php
// admin/plantillas.php
require_once '../config/db.php';
require_once '../core/plantillas.php';

// PRIMERO: Verificar sesión y permisos (ANTES de cualquier salida HTML)
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

// SOLO DESPUÉS de las validaciones, incluir el header
include '../templates/header.php';

$accion = $_GET['accion'] ?? 'listar';
$id_plantilla = $_GET['id'] ?? 0;
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-files"></i> Gestor de Plantillas de Marginación</h5>
            <?php if ($accion == 'listar'): ?>
                <a href="?accion=nueva" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-circle"></i> Nueva Plantilla
                </a>
            <?php elseif ($accion == 'ver_campos'): ?>
                <a href="?accion=listar" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver a Plantillas
                </a>
            <?php else: ?>
                <a href="?accion=listar" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            
            <?php if ($accion == 'listar'): ?>
                <!-- BUSCADOR EN TIEMPO REAL -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="input-group" style="max-width: 350px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" id="filtroPlantillas" class="form-control" placeholder="Buscar plantilla...">
                        <button class="btn btn-outline-secondary" id="limpiarFiltro" type="button"><i class="bi bi-eraser"></i></button>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-info-circle"></i> Haga clic en una fila para editar
                    </div>
                </div>

                <!-- TABLA DE PLANTILLAS -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tablaPlantillas">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Trámite</th>
                                <th>Tipo Asiento</th>
                                <th>Tipo Acto</th>
                                <th>Tipo Marginación</th>
                                <th>Campos</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * FROM plantillas_textos ORDER BY tipo_asiento, nombre_tramite";
                            $result = $conn->query($sql);
                            while($row = $result->fetch_assoc()):
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM plantillas_campos WHERE plantilla_id = ?");
                                $stmt->bind_param("i", $row['id']);
                                $stmt->execute();
                                $total_campos = $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nombre_tramite']) ?></td>
                                <td><span class="badge bg-info"><?= $row['tipo_asiento'] ?? 'N/A' ?></span></td>
                                <td><span class="badge bg-secondary"><?= $row['tipo_acto'] ?? 'N/A' ?></span></td>
                                <td><span class="badge bg-dark"><?= $row['tipo_marginacion'] ?? 'N/A' ?></span></td>
                                <td>
                                    <span class="badge bg-primary"><?= $total_campos ?> campos</span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $row['activo'] ? 'success' : 'danger' ?>">
                                        <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?accion=editar&id=<?= $row['id'] ?>" class="btn btn-warning" title="Editar Plantilla">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?accion=ver_campos&id=<?= $row['id'] ?>" class="btn btn-info" title="Ver Campos">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="?accion=campos&id=<?= $row['id'] ?>" class="btn btn-secondary" title="Configurar Campos">
                                            <i class="bi bi-ui-radios"></i>
                                        </a>
                                        <a href="../public/nueva.php?p=<?= $row['id'] ?>" target="_blank" class="btn btn-success" title="Probar Plantilla">
                                            <i class="bi bi-play"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($accion == 'ver_campos'): 
                // =============================================
                // NUEVA ACCIÓN: VER CAMPOS DE LA PLANTILLA
                // =============================================
                $plantilla = PlantillaManager::getPlantillaCompleta($conn, $id_plantilla);
                if (!$plantilla):
            ?>
                <div class="alert alert-danger">Plantilla no encontrada</div>
                <a href="?accion=listar" class="btn btn-primary">Volver a Plantillas</a>
            <?php else: 
                $campos = $plantilla['campos'] ?? [];
            ?>
                <!-- INFORMACIÓN DE LA PLANTILLA -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-file-text-fill"></i> Información de la Plantilla</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 150px;">ID:</td>
                                        <td><?= $plantilla['id'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Nombre del Trámite:</td>
                                        <td><strong><?= htmlspecialchars($plantilla['nombre_tramite']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Descripción:</td>
                                        <td><?= nl2br(htmlspecialchars($plantilla['descripcion'] ?? 'Sin descripción')) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tipo de Asiento:</td>
                                        <td><span class="badge bg-info"><?= $plantilla['tipo_asiento'] ?? 'N/A' ?></span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-bold" style="width: 150px;">Tipo de Acto:</td>
                                        <td><span class="badge bg-secondary"><?= $plantilla['tipo_acto'] ?? 'N/A' ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tipo de Marginación:</td>
                                        <td><span class="badge bg-dark"><?= $plantilla['tipo_marginacion'] ?? 'N/A' ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Requiere Cónyuge:</td>
                                        <td><?= $plantilla['requiere_conyuge'] ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Requiere Leyenda:</td>
                                        <td><?= $plantilla['requiere_leyenda'] ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Estado:</td>
                                        <td><?= $plantilla['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Inactivo</span>' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LISTA DE CAMPOS DE LA PLANTILLA -->
                <div class="card">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-ui-radios"></i> Campos Configurados</h6>
                        <span class="badge bg-light text-dark"><?= count($campos) ?> campos</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($campos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="bi bi-exclamation-triangle"></i> Esta plantilla no tiene campos configurados.
                                <a href="?accion=campos&id=<?= $id_plantilla ?>" class="alert-link">Configurar campos ahora</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre del Campo</th>
                                            <th>Etiqueta</th>
                                            <th>Tipo</th>
                                            <th>Requerido</th>
                                            <th>Orden</th>
                                            <th>Opciones</th>
                                            <th>Ayuda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campos as $index => $campo): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <code class="bg-light p-1">{<?= htmlspecialchars($campo['nombre_campo']) ?>}</code>
                                                </td>
                                                <td><?= htmlspecialchars($campo['etiqueta']) ?></td>
                                                <td>
                                                    <?php 
                                                    $tipo_badge = [
                                                        'text' => 'primary',
                                                        'date' => 'success',
                                                        'fecha_letras' => 'success',
                                                        'textarea' => 'info',
                                                        'select' => 'warning',
                                                        'funcionario' => 'danger',
                                                        'lugar' => 'secondary',
                                                        'referencia_legal' => 'dark'
                                                    ];
                                                    $badge_class = $tipo_badge[$campo['tipo_campo']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= $campo['tipo_campo'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <?= $campo['requerido'] ? '<i class="bi bi-check-circle-fill text-success" title="Requerido"></i>' : '<i class="bi bi-circle text-secondary" title="Opcional"></i>' ?>
                                                </td>
                                                <td class="text-center"><?= $campo['orden'] ?></td>
                                                <td>
                                                    <?php if (!empty($campo['opciones'])): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                onclick="verOpciones(<?= htmlspecialchars(json_encode($campo['opciones'])) ?>)">
                                                            <i class="bi bi-list"></i> Ver opciones
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($campo['ayuda'] ?? '—') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- VISTA PREVIA DEL TEXTO DE LA PLANTILLA -->
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="bi bi-file-text"></i> Texto de la Plantilla</h6>
                    </div>
                    <div class="card-body">
                        <pre class="bg-light p-3 rounded" style="white-space: pre-wrap; font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($plantilla['cuerpo_legal']) ?></pre>
                    </div>
                </div>

                <!-- VARIABLES UTILIZADAS EN LA PLANTILLA -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-code-square"></i> Variables utilizadas en el texto</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        preg_match_all('/\{(.*?)\}/', $plantilla['cuerpo_legal'], $matches);
                        $variables = array_unique($matches[1]);
                        
                        $variables_sistema = ['o_a', 'leyenda_apellido', 'fecha_hoy_letras', 'nombre_conyuge', 'nombre_el', 'nombre_ella', 'lugar', 'tipo_asiento_texto'];
                        $variables_sistema_encontradas = [];
                        $variables_personalizadas = [];
                        
                        foreach ($variables as $var) {
                            if (in_array($var, $variables_sistema)) {
                                $variables_sistema_encontradas[] = $var;
                            } else {
                                $variables_personalizadas[] = $var;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($variables_sistema_encontradas)): ?>
                            <div class="mb-3">
                                <strong class="text-primary">Variables del Sistema:</strong>
                                <div class="mt-2">
                                    <?php foreach ($variables_sistema_encontradas as $var): ?>
                                        <span class="badge bg-primary m-1"><code>{<?= $var ?>}</code></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($variables_personalizadas)): ?>
                            <div>
                                <strong class="text-success">Variables Personalizadas:</strong>
                                <div class="mt-2">
                                    <?php foreach ($variables_personalizadas as $var): ?>
                                        <span class="badge bg-success m-1"><code>{<?= $var ?>}</code></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($variables)): ?>
                            <p class="text-muted mb-0">No se encontraron variables en el texto de la plantilla.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="?accion=editar&id=<?= $id_plantilla ?>" class="btn btn-warning">
                        <i class="bi bi-pencil"></i> Editar Plantilla
                    </a>
                    <a href="?accion=campos&id=<?= $id_plantilla ?>" class="btn btn-secondary">
                        <i class="bi bi-ui-radios"></i> Configurar Campos
                    </a>
                    <a href="../public/nueva.php?p=<?= $id_plantilla ?>" target="_blank" class="btn btn-success">
                        <i class="bi bi-play"></i> Probar Plantilla
                    </a>
                    <a href="?accion=listar" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>

                <script>
                function verOpciones(opciones) {
                    let texto = '';
                    try {
                        const opts = JSON.parse(opciones);
                        if (Array.isArray(opts)) {
                            texto = opts.join('\n');
                        } else {
                            texto = opciones;
                        }
                    } catch(e) {
                        texto = opciones;
                    }
                    alert('Opciones disponibles:\n' + texto);
                }
                </script>

            <?php endif; ?>

            <?php elseif ($accion == 'nueva' || $accion == 'editar'): 
                $plantilla = [
                    'id' => 0,
                    'nombre_tramite' => '',
                    'tipo_asiento' => 'NACIMIENTO',
                    'tipo_acto' => 'ADMINISTRATIVO',
                    'tipo_marginacion' => 'MODIFICACION',
                    'cuerpo_legal' => '',
                    'descripcion' => '',
                    'requiere_conyuge' => 0,
                    'requiere_leyenda' => 0,
                    'activo' => 1
                ];
                
                if ($id_plantilla > 0) {
                    $stmt = $conn->prepare("SELECT * FROM plantillas_textos WHERE id = ?");
                    $stmt->bind_param("i", $id_plantilla);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $plantilla = $row;
                    }
                }
            ?>
                <!-- FORMULARIO DE PLANTILLA -->
                <form method="POST" action="guardar_plantilla.php">
                    <input type="hidden" name="id" value="<?= $plantilla['id'] ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombre del Trámite *</label>
                            <input type="text" name="nombre_tramite" class="form-control" 
                                   value="<?= htmlspecialchars($plantilla['nombre_tramite']) ?>" required>
                            <div class="form-text">Ej: "Rectificación de Nombre por REF"</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?= htmlspecialchars($plantilla['descripcion'] ?? '') ?></textarea>
                            <div class="form-text">Breve descripción de cuándo se usa esta plantilla</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tipo de Asiento *</label>
                            <select name="tipo_asiento" class="form-select" required>
                                <option value="TODOS" <?= $plantilla['tipo_asiento'] == 'TODOS' ? 'selected' : '' ?>>📋 TODOS (Aplica a cualquier asiento)</option>
                                <option value="NACIMIENTO" <?= $plantilla['tipo_asiento'] == 'NACIMIENTO' ? 'selected' : '' ?>>📄 Nacimiento</option>
                                <option value="MATRIMONIO" <?= $plantilla['tipo_asiento'] == 'MATRIMONIO' ? 'selected' : '' ?>>💍 Matrimonio</option>
                                <option value="DEFUNCION" <?= $plantilla['tipo_asiento'] == 'DEFUNCION' ? 'selected' : '' ?>>⚰️ Defunción</option>
                                <option value="UNION_NO_MATRIMONIAL" <?= $plantilla['tipo_asiento'] == 'UNION_NO_MATRIMONIAL' ? 'selected' : '' ?>>❤️ Unión No Matrimonial</option>
                                <option value="DIVORCIO" <?= $plantilla['tipo_asiento'] == 'DIVORCIO' ? 'selected' : '' ?>>💔 Divorcio</option>
                                <option value="REGIMEN_PATRIMONIAL" <?= $plantilla['tipo_asiento'] == 'REGIMEN_PATRIMONIAL' ? 'selected' : '' ?>>💰 Régimen Patrimonial</option>
                                <option value="MARGINACION" <?= $plantilla['tipo_asiento'] == 'MARGINACION' ? 'selected' : '' ?>>📝 Marginación</option>
                            </select>
                            <div class="form-text">Seleccione "TODOS" si esta plantilla aplica a cualquier tipo de asiento</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tipo de Acto *</label>
                            <select name="tipo_acto" class="form-select" required>
                                <option value="ADMINISTRATIVO" <?= $plantilla['tipo_acto'] == 'ADMINISTRATIVO' ? 'selected' : '' ?>>Administrativo</option>
                                <option value="NOTARIAL" <?= $plantilla['tipo_acto'] == 'NOTARIAL' ? 'selected' : '' ?>>Notarial</option>
                                <option value="JUDICIAL" <?= $plantilla['tipo_acto'] == 'JUDICIAL' ? 'selected' : '' ?>>Judicial</option>
                                <option value="REF" <?= $plantilla['tipo_acto'] == 'REF' ? 'selected' : '' ?>>REF</option>
                                <option value="RNPN" <?= $plantilla['tipo_acto'] == 'RNPN' ? 'selected' : '' ?>>RNPN</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tipo de Marginación *</label>
                            <select name="tipo_marginacion" class="form-select" required>
                                <option value="RECTIFICACION" <?= $plantilla['tipo_marginacion'] == 'RECTIFICACION' ? 'selected' : '' ?>>Rectificación</option>
                                <option value="SUBSANACION" <?= $plantilla['tipo_marginacion'] == 'SUBSANACION' ? 'selected' : '' ?>>Subsanación</option>
                                <option value="MODIFICACION" <?= $plantilla['tipo_marginacion'] == 'MODIFICACION' ? 'selected' : '' ?>>Modificación</option>
                                <option value="CANCELACION" <?= $plantilla['tipo_marginacion'] == 'CANCELACION' ? 'selected' : '' ?>>Cancelación</option>
                                <option value="REPOSICION" <?= $plantilla['tipo_marginacion'] == 'REPOSICION' ? 'selected' : '' ?>>Reposición</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requiere_conyuge" class="form-check-input" value="1" 
                                       <?= $plantilla['requiere_conyuge'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Requiere datos del cónyuge</label>
                                <div class="form-text">Marcar si es para matrimonio o modificación de estado familiar</div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requiere_leyenda" class="form-check-input" value="1" 
                                       <?= $plantilla['requiere_leyenda'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Requiere leyenda de apellidos (para ella)</label>
                                <div class="form-text">Para marginaciones de matrimonio donde la cónyuge elige apellido</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Texto de la Plantilla *</label>
                        <textarea name="cuerpo_legal" class="form-control font-monospace" rows="12" required><?= htmlspecialchars($plantilla['cuerpo_legal']) ?></textarea>
                        <div class="form-text mt-2">
                            <strong>Variables disponibles:</strong><br>
                            <code>{nombre_el}</code> - Nombre de ÉL (inscrito)<br>
                            <code>{nombre_ella}</code> - Nombre de ELLA (inscrita)<br>
                            <code>{nombre_conyuge}</code> - Nombre del cónyuge (se asigna automáticamente según "Generar marginación para")<br>
                            <code>{o_a}</code> - Letra 'o' o 'a' según el caso<br>
                            <code>{leyenda_apellido}</code> - Leyenda de apellidos (solo si requiere_leyenda = 1)<br>
                            <code>{fecha_hoy_letras}</code> - Fecha actual en letras<br>
                            <code>{lugar}</code> - Lugar/distrito de la partida<br>
                            <code>{...}</code> - Variables personalizadas que definas en los campos
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="activo" class="form-check-input" value="1" <?= $plantilla['activo'] ? 'checked' : '' ?>>
                            <label class="form-check-label">Plantilla Activa</label>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" name="guardar_plantilla" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Guardar Plantilla
                        </button>
                        <a href="?accion=listar" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
                
            <?php elseif ($accion == 'campos'): 
                $plantilla = PlantillaManager::getPlantillaCompleta($conn, $id_plantilla);
                if (!$plantilla):
            ?>
                <div class="alert alert-danger">Plantilla no encontrada</div>
            <?php else: ?>
                <!-- CONFIGURACIÓN DE CAMPOS DE LA PLANTILLA -->
                <h5 class="mb-3">Configurando campos de: <strong><?= htmlspecialchars($plantilla['nombre_tramite']) ?></strong></h5>
                <p class="text-muted small">Define qué datos debe ingresar el usuario al usar esta plantilla. Los campos se mostrarán en el orden que definas.</p>
                
                <form method="POST" action="guardar_campos_plantilla.php">
                    <input type="hidden" name="plantilla_id" value="<?= $id_plantilla ?>">
                    
                    <div id="campos-container">
                        <?php 
                        $campo_index = 0;
                        $campos = $plantilla['campos'] ?? [];
                        foreach ($campos as $campo): 
                        ?>
                        <div class="card mb-3 campo-item border shadow-sm">
                            <div class="card-body">
                                <input type="hidden" name="campos[<?= $campo_index ?>][id]" value="<?= $campo['id'] ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Nombre del Campo</label>
                                        <input type="text" name="campos[<?= $campo_index ?>][nombre_campo]" 
                                               class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($campo['nombre_campo']) ?>" required>
                                        <div class="form-text text-muted" style="font-size: 0.7rem;">Sin llaves { }</div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Etiqueta</label>
                                        <input type="text" name="campos[<?= $campo_index ?>][etiqueta]" 
                                               class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($campo['etiqueta']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Tipo</label>
                                        <select name="campos[<?= $campo_index ?>][tipo_campo]" 
                                                class="form-select form-select-sm tipo-campo" 
                                                data-index="<?= $campo_index ?>">
                                            <option value="text" <?= $campo['tipo_campo'] == 'text' ? 'selected' : '' ?>>Texto</option>
                                            <option value="date" <?= $campo['tipo_campo'] == 'date' ? 'selected' : '' ?>>Fecha</option>
                                            <option value="fecha_letras" <?= $campo['tipo_campo'] == 'fecha_letras' ? 'selected' : '' ?>>Fecha (convertir a letras)</option>
                                            <option value="textarea" <?= $campo['tipo_campo'] == 'textarea' ? 'selected' : '' ?>>Área de texto</option>
                                            <option value="select" <?= $campo['tipo_campo'] == 'select' ? 'selected' : '' ?>>Selección</option>
                                            <option value="funcionario" <?= $campo['tipo_campo'] == 'funcionario' ? 'selected' : '' ?>>Funcionario</option>
                                            <option value="lugar" <?= $campo['tipo_campo'] == 'lugar' ? 'selected' : '' ?>>Lugar/Municipio</option>
                                            <option value="referencia_legal" <?= $campo['tipo_campo'] == 'referencia_legal' ? 'selected' : '' ?>>Referencia Legal</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Requerido</label>
                                        <select name="campos[<?= $campo_index ?>][requerido]" class="form-select form-select-sm">
                                            <option value="1" <?= $campo['requerido'] ? 'selected' : '' ?>>Sí</option>
                                            <option value="0" <?= !$campo['requerido'] ? 'selected' : '' ?>>No</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label class="small fw-bold">Orden</label>
                                        <input type="number" name="campos[<?= $campo_index ?>][orden]" 
                                               class="form-control form-control-sm" value="<?= $campo['orden'] ?>">
                                    </div>
                                </div>
                                
                                <div class="row mt-2 opciones-container" style="display: <?= $campo['tipo_campo'] == 'select' ? 'flex' : 'none' ?>">
                                    <div class="col-md-12">
                                        <label class="small fw-bold">Opciones (JSON)</label>
                                        <textarea name="campos[<?= $campo_index ?>][opciones]" 
                                                  class="form-control form-control-sm" rows="2" 
                                                  placeholder='["Opción 1", "Opción 2", "Opción 3"]'><?= htmlspecialchars($campo['opciones'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mt-2 ayuda-container">
                                    <div class="col-md-12">
                                        <label class="small fw-bold">Texto de ayuda (opcional)</label>
                                        <input type="text" name="campos[<?= $campo_index ?>][ayuda]" 
                                               class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($campo['ayuda'] ?? '') ?>"
                                               placeholder="Ej: Ejemplo: 2025-1234">
                                    </div>
                                </div>
                                
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-danger btn-sm eliminar-campo">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php 
                        $campo_index++;
                        endforeach; 
                        ?>
                    </div>
                    
                    <div class="text-center my-3">
                        <button type="button" id="agregar-campo" class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle"></i> Agregar Campo
                        </button>
                    </div>
                    
                    <div class="text-end mt-3 border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Guardar Campos
                        </button>
                        <a href="?accion=listar" class="btn btn-secondary">Volver</a>
                    </div>
                </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ================== FILTRO EN TIEMPO REAL PARA LA TABLA ==================
if (document.getElementById('filtroPlantillas')) {
    const filtroInput = document.getElementById('filtroPlantillas');
    const limpiarBtn = document.getElementById('limpiarFiltro');
    const tablaFilas = document.querySelectorAll('#tablaPlantillas tbody tr');

    function filtrarPlantillas() {
        const term = filtroInput.value.toLowerCase().trim();
        tablaFilas.forEach(fila => {
            const texto = fila.textContent.toLowerCase();
            fila.style.display = texto.includes(term) ? '' : 'none';
        });
    }

    filtroInput.addEventListener('input', filtrarPlantillas);
    limpiarBtn.addEventListener('click', function() {
        filtroInput.value = '';
        filtrarPlantillas();
    });

    // Clic en fila para editar la plantilla
    tablaFilas.forEach(fila => {
        fila.style.cursor = 'pointer';
        fila.addEventListener('click', function(e) {
            if (e.target.closest('a, button')) return;
            const editLink = this.querySelector('a.btn-warning');
            if (editLink) window.location.href = editLink.href;
        });
    });
}

// ================== VARIABLES PARA CAMPOS DINÁMICOS ==================
let campoCount = <?= isset($campo_index) ? $campo_index : 0 ?>;

// Agregar campo
const agregarBtn = document.getElementById('agregar-campo');
if (agregarBtn) {
    agregarBtn.addEventListener('click', function() {
        const container = document.getElementById('campos-container');
        if (!container) return;
        const newIndex = campoCount++;
        const html = `
            <div class="card mb-3 campo-item border shadow-sm">
                <div class="card-body">
                    <input type="hidden" name="campos[${newIndex}][id]" value="0">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="small fw-bold">Nombre del Campo</label>
                            <input type="text" name="campos[${newIndex}][nombre_campo]" class="form-control form-control-sm" required>
                            <div class="form-text text-muted" style="font-size: 0.7rem;">Sin llaves { }</div>
                        </div>
                        <div class="col-md-3">
                            <label class="small fw-bold">Etiqueta</label>
                            <input type="text" name="campos[${newIndex}][etiqueta]" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold">Tipo</label>
                            <select name="campos[${newIndex}][tipo_campo]" class="form-select form-select-sm tipo-campo" data-index="${newIndex}">
                                <option value="text">Texto</option>
                                <option value="date">Fecha</option>
                                <option value="fecha_letras">Fecha (convertir a letras)</option>
                                <option value="textarea">Área de texto</option>
                                <option value="select">Selección</option>
                                <option value="funcionario">Funcionario</option>
                                <option value="lugar">Lugar/Municipio</option>
                                <option value="referencia_legal">Referencia Legal</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold">Requerido</label>
                            <select name="campos[${newIndex}][requerido]" class="form-select form-select-sm">
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="small fw-bold">Orden</label>
                            <input type="number" name="campos[${newIndex}][orden]" class="form-control form-control-sm" value="${newIndex}">
                        </div>
                    </div>
                    <div class="row mt-2 opciones-container" style="display:none">
                        <div class="col-md-12">
                            <label class="small fw-bold">Opciones (JSON)</label>
                            <textarea name="campos[${newIndex}][opciones]" class="form-control form-control-sm" rows="2" placeholder='["Opción 1", "Opción 2", "Opción 3"]'></textarea>
                        </div>
                    </div>
                    <div class="row mt-2 ayuda-container">
                        <div class="col-md-12">
                            <label class="small fw-bold">Texto de ayuda (opcional)</label>
                            <input type="text" name="campos[${newIndex}][ayuda]" class="form-control form-control-sm" placeholder="Ej: Ejemplo: 2025-1234">
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-danger btn-sm eliminar-campo">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
}

// Mostrar/ocultar opciones según tipo
document.addEventListener('change', function(e) {
    if (e.target.classList && e.target.classList.contains('tipo-campo')) {
        const container = e.target.closest('.campo-item')?.querySelector('.opciones-container');
        if (container) {
            container.style.display = e.target.value === 'select' ? 'flex' : 'none';
        }
    }
});

// Eliminar campo
document.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('eliminar-campo')) {
        if (confirm('¿Eliminar este campo?')) {
            e.target.closest('.campo-item')?.remove();
        }
    }
});

function verOpciones(opciones) {
    let texto = '';
    try {
        const opts = JSON.parse(opciones);
        if (Array.isArray(opts)) {
            texto = opts.join('\n');
        } else {
            texto = opciones;
        }
    } catch(e) {
        texto = opciones;
    }
    alert('Opciones disponibles:\n' + texto);
}
</script>

<?php include '../templates/footer.php'; ?>