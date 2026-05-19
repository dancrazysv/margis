<?php 
require_once '../config/db.php'; 
require_once '../core/plantillas.php';
include '../templates/header.php'; 

if ($_SESSION['user_area'] !== 'MARGINADOR' && $_SESSION['user_area'] !== 'ADMINISTRADOR') {
    header("Location: dashboard.php?error=solo_marginadores");
    exit;
}

// 1. Lógica de numeración sugerida para el ID Digital
$id_sugerido = $_GET['id_format'] ?? '';
$anio_actual = ANIO_VIGENTE; 

if (!empty($id_sugerido) && strpos($id_sugerido, '-') !== false) {
    list($anio_doc, $num_doc) = explode('-', $id_sugerido);
} else {
    $anio_doc = $anio_actual;
    $stmt = $conn->prepare("SELECT MAX(CAST(NMargi1 AS UNSIGNED)) as u FROM margi WHERE LibroO = ?");
    $stmt->bind_param("s", $anio_doc);
    $stmt->execute();
    $num_doc = ($stmt->get_result()->fetch_assoc()['u'] ?? 0) + 1;
}

$p_id = $_GET['p'] ?? null; 

// Variables para la plantilla seleccionada
$requiere_conyuge = 0;
$requiere_leyenda = 0;
$tipo_asiento_actual = 'NACIMIENTO';
?>

<style>
    /* Estilos para la vista previa */
    .preview-card {
        position: sticky;
        top: 20px;
        background: #f8f9fa;
        border-radius: 16px;
        border-left: 4px solid #28a745;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .preview-card .card-header {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border-radius: 16px 16px 0 0;
    }
    
    .preview-content {
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        line-height: 1.7;
        max-height: 600px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        background: white;
        padding: 20px;
        border-radius: 8px;
    }
    
    .preview-content p {
        margin-bottom: 0.75rem;
    }
    
    .update-time {
        font-size: 0.7rem;
        color: #6c757d;
    }
    
    .btn-preview-toggle {
        transition: all 0.3s ease;
    }
    
    /* Reducir padding lateral para más espacio */
    .container-fluid, .container {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }
    
    .card-body {
        padding: 1.25rem !important;
    }
    
    /* Mejorar espaciado en móviles */
    @media (max-width: 768px) {
        .preview-card {
            position: relative;
            top: 0;
            margin-top: 20px;
        }
        .container-fluid, .container {
            padding-left: 12px !important;
            padding-right: 12px !important;
        }
        .card-body {
            padding: 1rem !important;
        }
    }
    
    /* Ajustes para filas de formulario */
    .row.g-2 {
        margin-left: -0.25rem;
        margin-right: -0.25rem;
    }
    .row.g-2 > [class*="col-"] {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }
    
    /* Mejorar legibilidad de la vista previa */
    .preview-content p {
        margin-bottom: 0.75rem;
    }
    
    .preview-content strong {
        color: #1e7e34;
    }
</style>

<div class="container-fluid mt-3 mb-4 px-3">
    <div class="row">
        <!-- Columna del formulario -->
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-plus"></i> Nueva Marginación Digital: <?php echo "$anio_doc-$num_doc"; ?></h5>
                    <?php if ($p_id): ?>
                    <button type="button" class="btn btn-sm btn-light btn-preview-toggle" id="togglePreviewBtn">
                        <i class="bi bi-eye-slash"></i> Ocultar vista previa
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form action="../actions/guardar_dinamico.php" method="POST" id="form-nueva">
                        <input type="hidden" name="anio_digital" value="<?php echo $anio_doc; ?>">
                        <input type="hidden" name="num_digital" value="<?php echo $num_doc; ?>">

                        <div class="row mb-4 bg-light p-3 rounded border">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-primary">1. Seleccione el Tipo de Marginación:</label>
                                <select name="plantilla_id" id="plantilla_id" class="form-select select2-init" required onchange="location.href='?id_format=<?php echo $id_sugerido; ?>&p='+this.value">
                                    <option value="">-- Seleccione una opción --</option>
                                    <?php 
                                    $plantillas = PlantillaManager::getPlantillasActivas($conn);
                                    foreach($plantillas as $p): 
                                        if ($p_id == $p['id']) {
                                            $requiere_conyuge = $p['requiere_conyuge'] ?? 0;
                                            $requiere_leyenda = $p['requiere_leyenda'] ?? 0;
                                        }
                                    ?>
                                        <option value="<?php echo $p['id']; ?>" 
                                            data-requiere-conyuge="<?= $p['requiere_conyuge'] ?? 0 ?>"
                                            data-requiere-leyenda="<?= $p['requiere_leyenda'] ?? 0 ?>"
                                            <?php echo ($p_id == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo $p['nombre_tramite']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($p_id): 
                            $plantilla = PlantillaManager::getPlantillaCompleta($conn, $p_id);
                            if (!$plantilla) {
                                echo '<div class="alert alert-danger">Plantilla no encontrada</div>';
                            } else {
                                $requiere_conyuge = $plantilla['requiere_conyuge'] ?? 0;
                                $requiere_leyenda = $plantilla['requiere_leyenda'] ?? 0;
                                $tipo_asiento_plantilla = $plantilla['tipo_asiento'] ?? 'NACIMIENTO';
                                
                                // Obtener campos personalizados de la tabla plantillas_campos
                                $stmt_campos = $conn->prepare("SELECT * FROM plantillas_campos WHERE plantilla_id = ? ORDER BY orden ASC");
                                $stmt_campos->bind_param("i", $p_id);
                                $stmt_campos->execute();
                                $campos_personalizados = $stmt_campos->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>

                            <!-- Control de Expediente -->
                            <div class="card mb-4 border-warning shadow-sm">
                                <div class="card-body bg-light">
                                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-hash"></i> Control de Expediente</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-danger">Número de Trámite (Obligatorio):</label>
                                            <div class="input-group">
                                                <input type="text" name="tramite_anio" class="form-control form-control-lg fw-bold text-center preview-field" style="max-width: 100px;" value="<?php echo $anio_doc; ?>">
                                                <span class="input-group-text bg-white fw-bold">-</span>
                                                <input type="text" name="tramite_correlativo" class="form-control form-control-lg fw-bold preview-field" placeholder="xxxx-xx" required pattern="[0-9]+-[0-9]+" title="Formato: números-guion-número">
                                            </div>
                                            <div class="form-text">Ajuste el año si el trámite fue recibido en el período anterior.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="num_tramite" id="num_tramite_hidden">

                            <!-- ===================================================== -->
                            <!-- DATOS DEL CÓNYUGE (SOLO SI ES NECESARIO) -->
                            <!-- ===================================================== -->
                            <?php 
                            $tiene_variables_conyuge = (strpos($plantilla['cuerpo_legal'], '{nombre_ella}') !== false || 
                                                        strpos($plantilla['cuerpo_legal'], '{nombre_el}') !== false ||
                                                        strpos($plantilla['cuerpo_legal'], '{nombre_conyuge}') !== false);
                            
                            if ($requiere_conyuge == 1 || $tiene_variables_conyuge): 
                            ?>
                            <div class="card border-primary mb-4 shadow-sm border-2">
                                <div class="card-body bg-light">
                                    <div class="row g-3">
                                        <?php if (strpos($plantilla['cuerpo_legal'], '{nombre_el}') !== false): ?>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">NOMBRE DE ÉL:</label>
                                            <input type="text" name="nombre_el" class="form-control preview-field" placeholder="Ej: JUAN CARLOS PEREZ">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (strpos($plantilla['cuerpo_legal'], '{nombre_ella}') !== false): ?>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">NOMBRE DE ELLA:</label>
                                            <input type="text" name="nombre_ella" class="form-control preview-field" placeholder="Ej: MARIA ELENA GARCIA">
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (strpos($plantilla['cuerpo_legal'], '{leyenda_apellido}') !== false && $requiere_leyenda == 1): ?>
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">Leyenda Apellidos (Ella):</label>
                                            <select name="leyenda_tipo" id="leyenda_tipo" class="form-select select2-init preview-field" onchange="toggleExterior(); actualizarPreview()">
                                                <option value="soltera">Seguirá usando sus apellidos de soltera</option>
                                                <option value="con_de">Usará "DE" + apellido del cónyuge</option>
                                                <option value="sin_de">Usará apellido del cónyuge sin "DE"</option>
                                                <option value="exterior">Exterior (Manual)</option>
                                            </select>
                                            <input type="text" name="apellidos_ext" id="ap_ext" class="form-control mt-2 preview-field" style="display:none;" placeholder="Escriba apellidos manuales">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                                <input type="hidden" name="nombre_el" value="">
                                <input type="hidden" name="nombre_ella" value="">
                                <input type="hidden" name="leyenda_tipo" value="">
                                <input type="hidden" name="apellidos_ext" value="">
                            <?php endif; ?>

                            <!-- Datos de Foliación Física -->
                            <div class="card mb-4 border-info shadow-sm">
                                <div class="card-body">
                                    <h6 class="fw-bold text-info border-bottom pb-2">3. Datos de Foliación Física</h6>
                                    <div id="foliacion_1">
                                        <p class="small fw-bold text-primary mb-1">Partida Principal:</p>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Tipo Partida</label>
                                                <select name="tipo_p_1" id="tipo_p_1" class="form-select select2-init preview-field" required>
                                                    <?php $tps = $conn->query("SELECT nombre_partida FROM tipo_partida WHERE grupo_partida = 1 ORDER BY nombre_partida ASC"); 
                                                    while($t = $tps->fetch_assoc()) echo "<option value='{$t['nombre_partida']}'>{$t['nombre_partida']}</option>"; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="small fw-bold">Distrito / Municipio</label>
                                                <select name="lugar" class="form-select select2-init preview-field" required>
                                                    <option value="">-- Seleccione un distrito --</option>
                                                    <?php 
                                                    $distritos = $conn->query("SELECT nombre FROM distritos WHERE activo = 1 ORDER BY nombre ASC");
                                                    while($d = $distritos->fetch_assoc()):
                                                    ?>
                                                        <option value="<?= htmlspecialchars($d['nombre']) ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <div class="form-text text-muted small">Distritos de San Salvador Centro.</div>
                                            </div>
                                            <div class="col-md-2"><label class="small fw-bold">Año</label><input type="text" name="anio_p_1" class="form-control preview-field" required></div>
                                            <div class="col-md-2"><label class="small fw-bold">Libro</label><input type="text" name="libro_p_1" class="form-control preview-field" required></div>
                                            <div class="col-md-2"><label class="small fw-bold">Partida</label><input type="text" name="n_partida_1" class="form-control preview-field" required></div>
                                            <div class="col-md-2"><label class="small fw-bold">Folio</label><input type="text" name="folio_p_1" class="form-control preview-field" required></div>
                                            <div class="col-md-2"><label class="small fw-bold">Tomo</label><input type="text" name="tomo_p_1" class="form-control preview-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalles del Acto Jurídico -->
                            <div class="row g-3">
                                <h6 class="fw-bold text-success border-bottom pb-2">4. Detalles del Acto Jurídico</h6>
                                <?php 
                                preg_match_all('/\{(.*?)\}/', $plantilla['cuerpo_legal'], $matches);
                                $variables = array_unique($matches[1]);
                                
                                $variables_sistema = ['o_a', 'leyenda_apellido', 'fecha_hoy_letras', 'nombre_conyuge', 'nombre_el', 'nombre_ella', 'lugar', 'tipo_asiento_texto'];
                                
                                $labels = [
                                    'datos_funcionario' => 'Datos del Funcionario (Cargo y Nombre)',
                                    'nombre_funcionario' => 'Nombre del Funcionario',
                                    'cargo_funcionario' => 'Cargo del Funcionario',
                                    'referencia_legal' => 'Referencia Legal / Documento de Respaldo',
                                    'nombre_correcto' => 'Nombre correcto',
                                    'apellidos_correctos' => 'Apellidos correctos',
                                    'fecha_resolucion' => 'Fecha de la Resolución',
                                    'numero_escritura' => 'Número de Escritura Pública',
                                    'fecha_escritura' => 'Fecha de la Escritura',
                                    'numero_oficio' => 'Número de Oficio',
                                    'fecha_oficio' => 'Fecha del Oficio',
                                    'nombre_juzgado' => 'Nombre del Juzgado',
                                    'referencia_sentencia' => 'Referencia de la Sentencia',
                                    'fecha_sentencia' => 'Fecha de la Sentencia',
                                    'fecha_ejecutoria' => 'Fecha de Ejecutoria',
                                    'nombre_conocido' => 'Nombre por el que es conocido(a)',
                                    'lugar_boda' => 'Lugar de la Boda',
                                    'fecha_boda' => 'Fecha de la Boda',
                                    'fecha_boda_letras' => 'Fecha de la Boda',
                                ];
                                
                                foreach ($campos_personalizados as $campo):
                                    $nombre_campo = $campo['nombre_campo'];
                                    $etiqueta = $campo['etiqueta'] ?? ucwords(str_replace('_', ' ', $nombre_campo));
                                    $tipo_campo = $campo['tipo_campo'] ?? 'text';
                                    $requerido = $campo['requerido'] == 1 ? 'required' : '';
                                    $opciones = $campo['opciones'] ?? '';
                                    
                                    if(in_array($nombre_campo, $variables_sistema)) continue;
                                ?>
                                    <div class="col-md-6">
                                        <label class="small fw-bold"><?php echo htmlspecialchars($etiqueta); ?></label>
                                        
                                        <?php if($tipo_campo == 'select' && !empty($opciones)): ?>
                                            <select name="vars[<?php echo $nombre_campo; ?>]" class="form-select select2-init preview-field" <?php echo $requerido; ?>>
                                                <option value="">-- Seleccione --</option>
                                                <?php 
                                                $opts = explode(',', $opciones);
                                                foreach($opts as $opt): ?>
                                                    <option value="<?php echo trim($opt); ?>"><?php echo trim($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            
                                        <?php elseif($tipo_campo == 'date' || $tipo_campo == 'fecha_letras'): ?>
                                            <input type="date" name="vars[<?php echo $nombre_campo; ?>]" class="form-control fecha-a-letras preview-field" onchange="convertirFecha(this, '<?php echo $nombre_campo; ?>')" <?php echo $requerido; ?>>
                                            <input type="hidden" name="vars[<?php echo $nombre_campo; ?>_letras]" id="letras_<?php echo $nombre_campo; ?>">
                                            <input type="hidden" name="hid_<?php echo $nombre_campo; ?>" id="hid_<?php echo $nombre_campo; ?>">
                                            
                                        <?php elseif($tipo_campo == 'funcionario'): ?>
                                            <div class="p-2 border rounded bg-light">
                                                <select name="vars_cargo[<?php echo $nombre_campo; ?>]" class="form-select select2-tag mb-2 preview-field" data-var="<?php echo $nombre_campo; ?>" data-placeholder="Escriba o seleccione un cargo...">
                                                    <option value="">-- Escriba o seleccione --</option>
                                                    <?php $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                                                    while($c = $cargos->fetch_assoc()) echo "<option value='".htmlspecialchars($c['cargo'])."'>".htmlspecialchars($c['cargo'])."</option>"; ?>
                                                </select>
                                                <select name="vars_nombre[<?php echo $nombre_campo; ?>]" class="form-select select2-tag preview-field" data-var="<?php echo $nombre_campo; ?>" data-placeholder="Escriba o seleccione un nombre...">
                                                    <option value="">-- Escriba o seleccione --</option>
                                                    <?php $nots = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                                                    while($n = $nots->fetch_assoc()) echo "<option value='".htmlspecialchars($n['nombre'])."'>".htmlspecialchars($n['nombre'])."</option>"; ?>
                                                </select>
                                                <input type="hidden" name="vars[<?php echo $nombre_campo; ?>]" id="final_<?php echo $nombre_campo; ?>">
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="bi bi-info-circle"></i> Seleccione de la lista o escriba un valor nuevo
                                                </div>
                                            </div>
                                            
                                        <?php elseif($tipo_campo == 'lugar'): ?>
                                            <select name="vars[<?php echo $nombre_campo; ?>]" class="form-select select2-init preview-field" <?php echo $requerido; ?>>
                                                <option value="">-- Seleccione --</option>
                                                <?php $muns = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                                                while($m = $muns->fetch_assoc()) echo "<option value='".htmlspecialchars($m['municipio'])."'>".htmlspecialchars($m['municipio'])."</option>"; ?>
                                            </select>
                                            
                                        <?php elseif($tipo_campo == 'textarea'): ?>
                                            <textarea name="vars[<?php echo $nombre_campo; ?>]" class="form-control preview-field" rows="3" <?php echo $requerido; ?> placeholder="<?php echo htmlspecialchars($etiqueta); ?>"></textarea>
                                            
                                        <?php else: ?>
                                            <input type="text" name="vars[<?php echo $nombre_campo; ?>]" class="form-control preview-field" placeholder="<?php echo htmlspecialchars($etiqueta); ?>" <?php echo $requerido; ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php 
                                $campos_existentes = array_column($campos_personalizados, 'nombre_campo');
                                foreach ($variables as $v): 
                                    if(in_array($v, $variables_sistema)) continue;
                                    if(in_array($v, $campos_existentes)) continue;
                                    
                                    $label = $labels[$v] ?? ucwords(str_replace('_',' ',$v));
                                    $es_funcionario = preg_match('/funcionario|notario|cargo/', $v);
                                    $es_fecha = preg_match('/fecha/', $v) || $v == 'fecha_resolucion' || $v == 'fecha_escritura' || $v == 'fecha_oficio' || $v == 'fecha_sentencia' || $v == 'fecha_ejecutoria' || $v == 'fecha_boda';
                                    $es_lugar = preg_match('/lugar|municipio|distrito/', $v) || $v == 'lugar_boda';
                                    $es_referencia = ($v == 'referencia_legal');
                                    $req = ($es_funcionario || $es_fecha || $es_lugar || $es_referencia) ? '' : 'required';
                                ?>
                                    <div class="col-md-6">
                                        <label class="small fw-bold"><?php echo $label; ?></label>
                                        
                                        <?php if($es_referencia): ?>
                                            <div class="p-2 border rounded bg-light">
                                                <select id="tipo_ref" class="form-select select2-init mb-2 preview-field" onchange="toggleRefFields()">
                                                    <option value="">-- Tipo Documento --</option>
                                                    <option value="escritura">Escritura Pública</option>
                                                    <option value="acta">Acta Matrimonial</option>
                                                    <option value="partida">Partida (Distrito Externo)</option>
                                                </select>
                                                <div id="campos_partida_ext" style="display:none;" class="row g-2">
                                                    <div class="col-md-4"><input type="text" id="ref_an" class="form-control preview-field" placeholder="Año"></div>
                                                    <div class="col-md-4"><input type="text" id="ref_li" class="form-control preview-field" placeholder="Libro"></div>
                                                    <div class="col-md-4"><input type="text" id="ref_as" class="form-control preview-field" placeholder="As."></div>
                                                    <div class="col-md-4"><input type="text" id="ref_fo" class="form-control preview-field" placeholder="Fol."></div>
                                                    <div class="col-md-4"><input type="text" id="ref_to" class="form-control preview-field" placeholder="Tom."></div>
                                                    <div class="col-md-12 mt-1">
                                                        <select id="ref_dist" class="form-select select2-init preview-field">
                                                            <option value="">-- Municipio/Distrito --</option>
                                                            <?php $m=$conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC"); while($r=$m->fetch_assoc()): ?>
                                                                <option value='<?= htmlspecialchars($r['municipio']) ?>'><?= htmlspecialchars($r['municipio']) ?></option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="campos_doc" style="display:none;"><input type="text" id="ref_num" class="form-control mb-2 preview-field" placeholder="Número"></div>
                                                <input type="hidden" name="vars[<?php echo $v; ?>]" id="ref_final">
                                            </div>

                                        <?php elseif($es_funcionario): ?>
                                            <div class="p-2 border rounded bg-light">
                                                <label class="small text-muted mb-1">Seleccione o escriba Cargo y Funcionario:</label>
                                                <select name="vars_cargo[<?php echo $v; ?>]" class="form-select select2-tag mb-2 cargo-sel preview-field" data-var="<?php echo $v; ?>" data-placeholder="Escriba o seleccione un cargo...">
                                                    <option value="">-- Escriba o seleccione --</option>
                                                    <?php $cargos = $conn->query("SELECT cargo FROM cargo_juridico ORDER BY cargo ASC");
                                                    while($c = $cargos->fetch_assoc()) echo "<option value='".htmlspecialchars($c['cargo'])."'>".htmlspecialchars($c['cargo'])."</option>"; ?>
                                                </select>
                                                <select name="vars_nombre[<?php echo $v; ?>]" class="form-select select2-tag nombre-sel preview-field" data-var="<?php echo $v; ?>" data-placeholder="Escriba o seleccione un nombre...">
                                                    <option value="">-- Escriba o seleccione --</option>
                                                    <?php $nots = $conn->query("SELECT nombre FROM notarios ORDER BY nombre ASC");
                                                    while($n = $nots->fetch_assoc()) echo "<option value='".htmlspecialchars($n['nombre'])."'>".htmlspecialchars($n['nombre'])."</option>"; ?>
                                                </select>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="bi bi-info-circle"></i> Puede seleccionar de la lista o escribir un valor nuevo.
                                                </div>
                                                <input type="hidden" name="vars[<?php echo $v; ?>]" id="final_<?php echo $v; ?>">
                                            </div>

                                        <?php elseif($es_fecha): ?>
                                            <input type="date" name="vars[<?php echo $v; ?>]" class="form-control fecha-a-letras preview-field" onchange="convertirFecha(this, '<?php echo $v; ?>')" <?php echo $req; ?>>
                                            <input type="hidden" name="vars[<?php echo $v; ?>_letras]" id="letras_<?php echo $v; ?>">
                                            <input type="hidden" name="hid_<?php echo $v; ?>" id="hid_<?php echo $v; ?>">

                                        <?php elseif($es_lugar): ?>
                                            <select name="vars[<?php echo $v; ?>]" class="form-select select2-init preview-field" <?php echo $req; ?>>
                                                <option value="">-- Seleccione Lugar --</option>
                                                <?php $muns = $conn->query("SELECT municipio FROM municipios ORDER BY municipio ASC");
                                                while($m = $muns->fetch_assoc()) echo "<option value='".htmlspecialchars($m['municipio'])."'>".htmlspecialchars($m['municipio'])."</option>"; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="text" name="vars[<?php echo $v; ?>]" class="form-control preview-field" <?php echo $req; ?>>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-5 text-end pt-3 border-top">
                                <button type="button" id="btnGuardarMarginacion" class="btn btn-success btn-lg px-5 shadow">
                                    <i class="bi bi-save"></i> Guardar Marginación
                                </button>
                            </div>
                        <?php 
                            }
                        endif; 
                        ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Columna de Vista Previa -->
        <?php if ($p_id && $plantilla): ?>
        <div class="col-lg-4" id="previewCol">
            <div class="card preview-card">
                <div class="card-header text-white">
                    <h6 class="mb-0"><i class="bi bi-eye-fill"></i> Vista Previa en Vivo</h6>
                </div>
                <div class="card-body p-3">
                    <div class="alert alert-info alert-sm mb-3 py-1">
                        <i class="bi bi-info-circle-fill"></i>
                        <small>La vista previa se actualiza automáticamente</small>
                    </div>
                    <div class="preview-content" id="livePreview">
                        <div class="text-center text-muted">
                            <i class="bi bi-hourglass-split"></i>
                            <p class="mt-2">Cargando vista previa...</p>
                        </div>
                    </div>
                    <div class="update-time text-end mt-2">
                        <small><i class="bi bi-clock"></i> Actualización en vivo</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación de duplicados -->
<div class="modal fade" id="modalDuplicados" tabindex="-1" aria-labelledby="modalDuplicadosLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalDuplicadosLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Posible Marginación Duplicada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="modalDuplicadosBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Verificando duplicados...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-arrow-left"></i> Regresar y corregir
                </button>
                <button type="button" class="btn btn-danger" id="btnContinuarDuplicado">
                    <i class="bi bi-check-circle"></i> Continuar (Guardar de todos modos)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Plantilla de texto para vista previa
const plantillaTexto = <?php echo json_encode($plantilla['cuerpo_legal'] ?? ''); ?>;

// Función para actualizar la vista previa en vivo
function actualizarPreview() {
    if (!plantillaTexto) return;
    
    let texto = plantillaTexto;
    
    // =====================================================
    // RECOLECTAR TODOS LOS VALORES DEL FORMULARIO
    // =====================================================
    const variables = {};
    
    // Fecha actual en letras
    const hoy = new Date();
    const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
    variables.fecha_hoy_letras = hoy.getDate() + " de " + meses[hoy.getMonth()] + " de " + hoy.getFullYear();
    
    // 1. Campos normales (input, select, textarea)
    $('#form-nueva').find('input, select, textarea').each(function() {
        let name = $(this).attr('name');
        if (!name) return;
        
        let value = $(this).val();
        
        // Limpiar value si es array
        if (Array.isArray(value)) {
            value = value.join(', ');
        }
        
        if (value && typeof value === 'string' && value.trim() !== '') {
            // Limpiar nombre de campo para variables
            let cleanName = name;
            // Extraer nombre de vars[algo]
            const match = name.match(/vars\[(.*?)\]/);
            if (match && match[1]) {
                cleanName = match[1];
            }
            variables[cleanName] = value.trim();
        }
    });
    
    // 2. Variables específicas importantes
    if ($('input[name="nombre_el"]').val()) variables.nombre_el = $('input[name="nombre_el"]').val();
    if ($('input[name="nombre_ella"]').val()) variables.nombre_ella = $('input[name="nombre_ella"]').val();
    if ($('select[name="lugar"]').val()) variables.lugar = $('select[name="lugar"]').val();
    
    // 3. Tipo de partida
    const tipoPartida = $('select[name="tipo_p_1"] option:selected').text();
    if (tipoPartida) variables.tipo_partida = tipoPartida;
    
    // 4. Datos de foliación física
    if ($('input[name="anio_p_1"]').val()) variables.anio_p = $('input[name="anio_p_1"]').val();
    if ($('input[name="libro_p_1"]').val()) variables.libro_p = $('input[name="libro_p_1"]').val();
    if ($('input[name="n_partida_1"]').val()) variables.n_partida = $('input[name="n_partida_1"]').val();
    if ($('input[name="folio_p_1"]').val()) variables.folio_p = $('input[name="folio_p_1"]').val();
    if ($('input[name="tomo_p_1"]').val()) variables.tomo_p = $('input[name="tomo_p_1"]').val();
    
    // 5. Número de trámite
    const tramiteAnio = $('input[name="tramite_anio"]').val();
    const tramiteCorr = $('input[name="tramite_correlativo"]').val();
    if (tramiteAnio && tramiteCorr) {
        variables.num_tramite = tramiteAnio + '-' + tramiteCorr;
    }
    
    // 6. Tipo de asiento texto
    const tipoAsiento = <?php echo json_encode($plantilla['tipo_asiento'] ?? 'NACIMIENTO'); ?>;
    const tiposAsiento = {
        'NACIMIENTO': 'de nacimiento',
        'MATRIMONIO': 'de matrimonio',
        'DEFUNCION': 'de defunción',
        'DIVORCIO': 'de divorcio'
    };
    variables.tipo_asiento_texto = tiposAsiento[tipoAsiento] || 'de nacimiento';
    
    // 7. O/a según sujeto
    const sujetoMat = $('select[name="sujeto_mat"]').val() || 'EL';
    variables.o_a = sujetoMat === 'EL' ? 'o' : 'a';
    
    // 8. Nombre del cónyuge
    if (sujetoMat === 'EL') {
        variables.nombre_conyuge = variables.nombre_ella || '';
    } else {
        variables.nombre_conyuge = variables.nombre_el || '';
    }
    
    // 9. Leyenda de apellido
    const leyendaTipo = $('select[name="leyenda_tipo"]').val();
    const apellidosExt = $('input[name="apellidos_ext"]').val();
    if (leyendaTipo === 'soltera') {
        variables.leyenda_apellido = "seguirá usando sus apellidos de soltera";
    } else if (leyendaTipo === 'con_de') {
        variables.leyenda_apellido = "usará " + (variables.nombre_el || '');
    } else if (leyendaTipo === 'sin_de') {
        variables.leyenda_apellido = "usará " + (variables.nombre_el || '');
    } else if (leyendaTipo === 'exterior') {
        variables.leyenda_apellido = apellidosExt || "";
    }
    
    // 10. Procesar campos de funcionario (vars_cargo + vars_nombre)
    $('[name^="vars_cargo"]').each(function() {
        const fullName = $(this).attr('name');
        const match = fullName.match(/vars_cargo\[(.*?)\]/);
        if (match && match[1]) {
            const key = match[1];
            const cargo = $(this).val() || '';
            const nombre = $(`[name="vars_nombre[${key}]"]`).val() || '';
            if (cargo || nombre) {
                variables[key] = (cargo + ' ' + nombre).trim();
            }
        }
    });
    
    // 11. Procesar fechas (convertir a letras)
    $('input[type="date"]').each(function() {
        const name = $(this).attr('name');
        const value = $(this).val();
        if (value && value.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const d = new Date(value);
            if (!isNaN(d.getTime())) {
                const fechaLetras = d.getDate() + " de " + meses[d.getMonth()] + " de " + d.getFullYear();
                if (name && name.includes('vars')) {
                    const match = name.match(/vars\[(.*?)\]/);
                    if (match && match[1]) {
                        variables[match[1]] = fechaLetras;
                        variables[match[1] + '_fecha'] = value;
                    }
                } else if (name === 'fecha_boda') {
                    variables.fecha_boda = fechaLetras;
                }
            }
        }
    });
    
    // 12. Referencia legal compuesta
    const tipoRef = $('#tipo_ref').val();
    if (tipoRef) {
        let refTexto = '';
        const numDoc = $('#ref_num').val() || '';
        const anio = $('#ref_an').val() || '';
        const libro = $('#ref_li').val() || '';
        const asiento = $('#ref_as').val() || '';
        const folio = $('#ref_fo').val() || '';
        const tomo = $('#ref_to').val() || '';
        const distrito = $('#ref_dist option:selected').text() || '';
        
        if (tipoRef === 'escritura') {
            refTexto = "certificación de testimonio de escritura pública número " + numDoc;
        } else if (tipoRef === 'acta') {
            refTexto = "certificación de acta matrimonial número " + numDoc;
        } else if (tipoRef === 'partida') {
            refTexto = "certificación de partida de matrimonio del Registro del Estado Familiar del " + distrito;
            if (asiento) refTexto += ", con número de asiento " + asiento;
            if (folio) refTexto += ", folio " + folio;
            if (tomo) refTexto += ", tomo " + tomo;
            if (libro) refTexto += ", libro " + libro;
            if (anio) refTexto += ", del año " + anio;
        }
        if (refTexto) variables.referencia_legal = refTexto;
    }
    
    // =====================================================
    // REEMPLAZAR VARIABLES EN EL TEXTO
    // =====================================================
    for (let [key, value] of Object.entries(variables)) {
        if (value) {
            const regex = new RegExp('\\{' + key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\}', 'g');
            texto = texto.replace(regex, value);
        }
    }
    
    // Eliminar variables no reemplazadas
    texto = texto.replace(/\{[^}]+\}/g, '');
    
// Limpiar texto (eliminar <br> no deseados al inicio)
texto = texto.replace(/^<br>/, '');
texto = texto.replace(/ante\s+ante/g, 'ante');
texto = texto.replace(/\s+/g, ' ');
texto = texto.trim();

// NO convertir puntos a <br>
// texto = texto.replace(/\. ([A-Z])/g, '.<br>$1');
// texto = texto.replace(/\.$/, '');


    // Mostrar en el preview
    const previewDiv = document.getElementById('livePreview');
    if (previewDiv) {
        if (texto) {
            previewDiv.innerHTML = `<div style="font-family: 'Courier New', monospace; font-size: 0.9rem; line-height: 1.7;">${escapeHtml(texto)}</div>`;
        } else {
            previewDiv.innerHTML = `<div class="text-center text-muted">
                <i class="bi bi-info-circle"></i>
                <p class="mt-2">Complete los campos para ver la vista previa...</p>
            </div>`;
        }
    }
}

// Función para mostrar/ocultar la vista previa
let previewVisible = true;

function togglePreview() {
    const previewCol = document.getElementById('previewCol');
    const toggleBtn = $('#togglePreviewBtn');
    
    if (previewVisible) {
        previewCol.style.display = 'none';
        toggleBtn.html('<i class="bi bi-eye-fill"></i> Mostrar vista previa');
        previewVisible = false;
    } else {
        previewCol.style.display = '';
        toggleBtn.html('<i class="bi bi-eye-slash"></i> Ocultar vista previa');
        previewVisible = true;
    }
}

$(document).ready(function() { 
    $('#tipo_ref').on('change', function() {
        const t = $(this).val();
        $('#campos_doc').toggle(t === 'escritura' || t === 'acta');
        $('#campos_partida_ext').toggle(t === 'partida');
        if (typeof window.armarRef === 'function') window.armarRef();
        actualizarPreview();
    });
    
    $('#leyenda_tipo').on('change', function() {
        $('#ap_ext').toggle($(this).val() === 'exterior');
        actualizarPreview();
    });
    
    // Actualizar vista previa cuando cambie cualquier campo
    $('#form-nueva').on('change keyup input', '.preview-field', function() {
        actualizarPreview();
    });
    
    // Actualizar vista previa cuando cambien selects de select2
    $('.select2-init').on('change', function() {
        setTimeout(actualizarPreview, 100);
    });
    
    // Actualizar vista previa cuando cambien selects de funcionario
    $(document).on('change', '.cargo-sel, .nombre-sel', function() {
        setTimeout(actualizarPreview, 100);
    });
    
    $('#ref_num, #ref_an, #ref_li, #ref_as, #ref_fo, #ref_to, #ref_dist').on('input change', function() {
        if (typeof window.armarRef === 'function') window.armarRef();
        actualizarPreview();
    });
    
    $('form').on('submit', function(e) {
        e.preventDefault();
        guardarFormulario(false);
        return false;
    });
    
    // Inicializar vista previa
    setTimeout(actualizarPreview, 500);
    
    // Botón toggle
    $('#togglePreviewBtn').on('click', togglePreview);
});

function toggleExterior() { 
    const apExt = document.getElementById('ap_ext');
    if (apExt) {
        apExt.style.display = document.getElementById('leyenda_tipo').value === 'exterior' ? 'block' : 'none';
    }
    actualizarPreview();
}

function toggleRefFields() { 
    const t = document.getElementById('tipo_ref')?.value; 
    const camposDoc = document.getElementById('campos_doc');
    const camposPartidaExt = document.getElementById('campos_partida_ext');
    if (camposDoc) camposDoc.style.display = (t === 'escritura' || t === 'acta') ? 'block' : 'none'; 
    if (camposPartidaExt) camposPartidaExt.style.display = (t === 'partida') ? 'flex' : 'none'; 
    if (typeof window.armarRef === 'function') window.armarRef(); 
    actualizarPreview();
}

function armarRef() {
    const t = document.getElementById('tipo_ref')?.value, 
          n = document.getElementById('ref_num')?.value || '', 
          a = document.getElementById('ref_as')?.value || '', 
          f = document.getElementById('ref_fo')?.value || '', 
          li = document.getElementById('ref_li')?.value || '', 
          to = document.getElementById('ref_to')?.value || '', 
          y = document.getElementById('ref_an')?.value || '';
    const d = $('#ref_dist').find('option:selected')?.text() || '';
    
    let res = "";
    if(t === 'escritura') res = "certificación de testimonio de escritura pública número " + n;
    else if(t === 'acta') res = "certificación de acta matrimonial número " + n;
    else if(t === 'partida') {
        res = "certificación de partida de matrimonio del Registro del Estado Familiar del " + d;
        if(a) res += ", con número de asiento " + a;
        if(f) res += ", folio " + f;
        if(to) res += ", tomo " + to;
        if(li) res += ", libro " + li;
        if(y) res += ", del año " + y;
    }
    const refFinal = document.getElementById('ref_final');
    if (refFinal) refFinal.value = res;
    actualizarPreview();
}

function convertirFecha(input, key) {
    if(!input.value) return;
    const d = new Date(input.value + 'T00:00:00');
    const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
    const fechaLetras = d.getDate() + " de " + meses[d.getMonth()] + " de " + d.getFullYear();
    const targetLetras = document.getElementById('letras_' + key);
    const targetHid = document.getElementById('hid_' + key);
    if (targetLetras) targetLetras.value = fechaLetras;
    if (targetHid) targetHid.value = input.value;
    actualizarPreview();
}

$(document).on('change', '.cargo-sel, .nombre-sel', function() {
    let varName = $(this).data('var');
    let cargo = $(`.cargo-sel[data-var="${varName}"]`).val() || "";
    let nombre = $(`.nombre-sel[data-var="${varName}"]`).val() || "";
    
    let textoFuncionario = "";
    if(cargo && nombre) {
        textoFuncionario = cargo + " " + nombre;
    } else if(cargo) {
        textoFuncionario = cargo;
    } else if(nombre) {
        textoFuncionario = nombre;
    }
    $(`#final_${varName}`).val(textoFuncionario);
    actualizarPreview();
});

// Función para guardar el formulario
function guardarFormulario(confirmarDuplicado = false) {
    const form = document.getElementById('form-nueva');
    const formData = new FormData(form);
    
    if (confirmarDuplicado) {
        formData.append('confirmar_duplicado', '1');
    }
    
    const btn = $('#btnGuardarMarginacion');
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Guardando...');
    
    $.ajax({
        url: '../actions/guardar_dinamico.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 60000,
        success: function(response) {
            btn.prop('disabled', false).html(originalText);
            
            if (response.status === 'success') {
                mostrarToast('success', response.message || 'Marginación guardada exitosamente');
                setTimeout(() => {
                    window.location.href = 'dashboard.php?msj=guardado';
                }, 1500);
            } else if (response.status === 'duplicado') {
                mostrarModalDuplicados(response.duplicados, response.datos_pendientes);
            } else {
                mostrarToast('error', response.message || 'Error al guardar');
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html(originalText);
            
            let mensajeError = 'Error en la comunicación con el servidor. ';
            
            if (xhr.status === 0) {
                mensajeError += 'No se pudo conectar al servidor.';
            } else if (xhr.status === 404) {
                mensajeError += 'El archivo guardar_dinamico.php no se encuentra.';
            } else if (xhr.status === 500) {
                mensajeError += 'Error interno del servidor.';
            } else {
                mensajeError += error;
            }
            
            mostrarToast('error', mensajeError);
            console.error('Error AJAX:', status, error, xhr.responseText);
        }
    });
}

// Función para mostrar el modal con los duplicados
function mostrarModalDuplicados(duplicados, datosPendientes) {
    const modalBody = $('#modalDuplicadosBody');
    
    let html = `
        <div class="alert alert-warning">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Se encontraron marginaciones existentes con los mismos datos clave:</strong>
            <ul class="mt-2 mb-0">
                <li><strong>Partida N°:</strong> ${escapeHtml(datosPendientes.n_partida_1 || 'N/A')}</li>
                <li><strong>Año Digital:</strong> ${escapeHtml(datosPendientes.anio_digital || 'N/A')}</li>
                <li><strong>Fecha del Evento:</strong> ${escapeHtml(datosPendientes.fecha_evento || 'N/A')}</li>
                <li><strong>Tipo de Partida:</strong> ${escapeHtml(datosPendientes.iniciales_partida || 'N/A')}</li>
            </ul>
        </div>
        
        <h6 class="fw-bold mt-3">Marginaciones existentes:</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr><th>ID Digital</th><th>Texto de Marginación</th><th>Fecha de Creación</th><th>Estado</th></tr>
                </thead>
                <tbody>`;
    
    if (duplicados && duplicados.length > 0) {
        duplicados.forEach(dup => {
            html += `<tr>
                        <td><code>${escapeHtml(dup.libro_nmargi_concat)}</code></td>
                        <td>${escapeHtml(dup.TxtMargi1 ? dup.TxtMargi1.substring(0, 150) : '')}${dup.TxtMargi1 && dup.TxtMargi1.length > 150 ? '...' : ''}</td>
                        <td>${escapeHtml(dup.FechaC)}</td>
                        <td><span class="badge bg-secondary">${escapeHtml(dup.estado)}</span></td>
                    </tr>`;
        });
    } else {
        html += `<tr><td colspan="4" class="text-center">No se encontraron duplicados</td></tr>`;
    }
    
    html += `</tbody></table></div>
        <div class="alert alert-danger mt-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>¿Desea continuar guardando esta marginación?</strong> Si continúa, se creará un duplicado.
        </div>`;
    
    modalBody.html(html);
    
    const modal = new bootstrap.Modal(document.getElementById('modalDuplicados'));
    modal.show();
}

function continuarConDuplicado() {
    const form = document.getElementById('form-nueva');
    const formData = new FormData(form);
    formData.append('confirmar_duplicado', '1');
    
    $.ajax({
        url: '../actions/guardar_dinamico.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDuplicados'));
                if (modal) modal.hide();
                
                mostrarToast('success', response.message || 'Marginación guardada exitosamente');
                setTimeout(() => {
                    window.location.href = 'dashboard.php?msj=guardado';
                }, 1500);
            } else {
                mostrarToast('error', response.message || 'Error al guardar');
            }
        },
        error: function() {
            mostrarToast('error', 'Error en la comunicación con el servidor');
        }
    });
}

function mostrarToast(tipo, mensaje) {
    const bgColor = tipo === 'success' ? 'bg-success' : 'bg-danger';
    const icono = tipo === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    
    const toast = $(`
        <div class="toast align-items-center text-white ${bgColor} border-0 position-fixed bottom-0 end-0 m-3" 
             role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000" 
             style="z-index: 1100;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icono} me-2"></i> ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    $('body').append(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.on('hidden.bs.toast', function() {
        toast.remove();
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

$('#btnGuardarMarginacion').on('click', function(e) {
    e.preventDefault();
    guardarFormulario(false);
});

$(document).on('click', '#btnContinuarDuplicado', function() {
    continuarConDuplicado();
});

$('#form-nueva').on('submit', function(e) {
    e.preventDefault();
    var anio = $('input[name="tramite_anio"]').val();
    var correlativo = $('input[name="tramite_correlativo"]').val();
    if(anio && correlativo) {
        $('#num_tramite_hidden').val(anio + '-' + correlativo);
    }
    guardarFormulario(false);
    return false;
});
</script>

<?php include '../templates/footer.php'; ?>