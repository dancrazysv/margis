<?php
// actions/guardar_dinamico.php - VERSIÓN DEFINITIVA
header('Content-Type: application/json');

// Desactivar errores en pantalla
error_reporting(0);
ini_set('display_errors', 0);

// Limpiar cualquier salida previa
if (ob_get_level()) ob_end_clean();

require_once '../config/db.php';
require_once '../core/plantillas.php';

// Función para enviar respuesta JSON
function responder($status, $mensaje, $extra = []) {
    $respuesta = array_merge(['status' => $status, 'message' => $mensaje], $extra);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function fechaEnLetrasDesdeIso($fechaIso) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$fechaIso)) {
        return '';
    }
    $timestamp = strtotime($fechaIso);
    if (!$timestamp) return '';
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('j', $timestamp) . ' de ' . $meses[(int)date('n', $timestamp) - 1] . ' de ' . date('Y', $timestamp);
}

function normalizarTexto($valor) {
    return trim((string)$valor);
}

function obtenerFechaEventoDesdeVars($vars) {
    if (!is_array($vars)) return '';
    if (!empty($vars['fecha_evento']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vars['fecha_evento'])) {
        return $vars['fecha_evento'];
    }
    foreach ($vars as $key => $value) {
        if (strpos($key, '_letras') !== false) continue;
        if (!preg_match('/^fecha/i', $key)) continue;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
            return $value;
        }
    }
    return '';
}

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    responder('error', 'Sesión no iniciada');
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder('error', 'Método no permitido');
}

// =====================================================
// RECOLECTAR DATOS
// =====================================================
$confirmar_duplicado = isset($_POST['confirmar_duplicado']) && $_POST['confirmar_duplicado'] == 1;

// Datos básicos
$libro_o = trim($_POST['anio_digital'] ?? date('Y'));
$nmargi1 = trim($_POST['num_digital'] ?? '0');
$n_partida = trim($_POST['n_partida_1'] ?? '');
$anio_p = trim($_POST['anio_p_1'] ?? '');
$libro_p = trim($_POST['libro_p_1'] ?? '');
$folio_o = trim($_POST['folio_p_1'] ?? '');
$tomo_p = trim($_POST['tomo_p_1'] ?? '');
$tipo_p_nombre = trim($_POST['tipo_p_1'] ?? '');
$lugar = trim($_POST['lugar'] ?? '');
$nombre_el = trim($_POST['nombre_el'] ?? '');
$nombre_ella = trim($_POST['nombre_ella'] ?? '');
$plantilla_id = (int)($_POST['plantilla_id'] ?? 0);
$sujeto_mat = trim($_POST['sujeto_mat'] ?? 'EL');
$leyenda_tipo = trim($_POST['leyenda_tipo'] ?? '');
$apellidos_ext = trim($_POST['apellidos_ext'] ?? '');
$vars = isset($_POST['vars']) && is_array($_POST['vars']) ? $_POST['vars'] : [];
$vars_cargo = isset($_POST['vars_cargo']) && is_array($_POST['vars_cargo']) ? $_POST['vars_cargo'] : [];
$vars_nombre = isset($_POST['vars_nombre']) && is_array($_POST['vars_nombre']) ? $_POST['vars_nombre'] : [];

// Foliación obligatoria
if ($folio_o === '') {
    responder('error', 'El campo folio es obligatorio.');
}

// Número de trámite
$num_tramite = trim($_POST['num_tramite'] ?? '');
if (empty($num_tramite) && isset($_POST['tramite_anio']) && isset($_POST['tramite_correlativo'])) {
    $num_tramite = trim($_POST['tramite_anio']) . '-' . trim($_POST['tramite_correlativo']);
}

// Fecha evento
$fecha_evento = obtenerFechaEventoDesdeVars($vars);
if (empty($fecha_evento) && !empty($_POST['fecha_boda']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['fecha_boda'])) {
    $fecha_evento = $_POST['fecha_boda'];
}

// =====================================================
// OBTENER INICIALES DEL TIPO DE PARTIDA
// =====================================================
$iniciales_partida = '';
if (!empty($tipo_p_nombre)) {
    $stmt = $conn->prepare("SELECT iniciales_partida FROM tipo_partida WHERE nombre_partida = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tipo_p_nombre);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) $iniciales_partida = $row['iniciales_partida'];
        $stmt->close();
    }
}
if (empty($iniciales_partida)) {
    $iniciales_partida = substr(preg_replace('/[^A-Za-z]/', '', $tipo_p_nombre), 0, 3);
}

// =====================================================
// VERIFICAR DUPLICADOS (si aplica)
// =====================================================
if (!$confirmar_duplicado && !empty($fecha_evento) && !empty($n_partida)) {
    $check = $conn->prepare("SELECT id, libro_nmargi_concat, TxtMargi1, FechaC, estado FROM margi WHERE NPartida = ? AND LibroO = ? AND fechaevento = ? LIMIT 5");
    if ($check) {
        $check->bind_param("sss", $n_partida, $libro_o, $fecha_evento);
        $check->execute();
        $duplicados = $check->get_result()->fetch_all(MYSQLI_ASSOC);
        $check->close();
        
        if (count($duplicados) > 0) {
            responder('duplicado', 'Se encontraron registros duplicados', [
                'duplicados' => $duplicados,
                'datos_pendientes' => [
                    'n_partida_1' => $n_partida,
                    'anio_digital' => $libro_o,
                    'fecha_evento' => $fecha_evento,
                    'iniciales_partida' => $iniciales_partida
                ]
            ]);
        }
    }
}

// =====================================================
// RENDERIZAR TEXTO DE LA PLANTILLA
// =====================================================
$texto_final = '';
if ($plantilla_id > 0) {
    $plantilla = PlantillaManager::getPlantillaCompleta($conn, $plantilla_id);
    if ($plantilla) {
        $datos_render = [];

        foreach ($vars as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
            if ($key === '') continue;
            $valor = normalizarTexto($value);
            if ($valor === '') continue;
            $datos_render[$key] = $valor;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                $letras = fechaEnLetrasDesdeIso($valor);
                if ($letras !== '') {
                    $datos_render[$key . '_letras'] = $letras;
                    $datos_render[$key] = $letras; // Compatibilidad con vista previa y textos legales
                }
            }
        }

        foreach ($vars_cargo as $key => $cargo) {
            $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
            if ($key === '') continue;
            $cargo = normalizarTexto($cargo);
            $nombre = normalizarTexto($vars_nombre[$key] ?? '');
            if ($cargo !== '' || $nombre !== '') {
                $datos_render[$key] = trim($cargo . ' ' . $nombre);
            }
        }

        $datos_render['lugar'] = $lugar;
        $datos_render['nombre_el'] = $nombre_el;
        $datos_render['nombre_ella'] = $nombre_ella;
        $datos_render['leyenda_apellido'] = PlantillaManager::armarLeyendaApellido($leyenda_tipo, $nombre_el, $apellidos_ext);

        if (!empty($num_tramite)) {
            $datos_render['num_tramite'] = $num_tramite;
        }
        if (!empty($anio_p)) $datos_render['anio_p'] = $anio_p;
        if (!empty($libro_p)) $datos_render['libro_p'] = $libro_p;
        if (!empty($n_partida)) $datos_render['n_partida'] = $n_partida;
        if (!empty($folio_o)) $datos_render['folio_p'] = $folio_o;
        if (!empty($tomo_p)) $datos_render['tomo_p'] = $tomo_p;
        if (!empty($fecha_evento)) {
            $datos_render['fecha_evento'] = fechaEnLetrasDesdeIso($fecha_evento) ?: $fecha_evento;
            $datos_render['fecha_evento_iso'] = $fecha_evento;
        }

        $texto_final = PlantillaManager::renderizarTexto(
            $plantilla['cuerpo_legal'],
            $datos_render,
            $sujeto_mat,
            $nombre_el,
            $nombre_ella,
            $plantilla['tipo_asiento'] ?? 'NACIMIENTO'
        );
    }
}

// =====================================================
// PREPARAR DATOS PARA INSERTAR
// =====================================================
$fecha_actual = date('Y-m-d');
$hora_actual = date('H:i:s');
$usuario = $_SESSION['iniciales'] ?? 'SIS';
$concatenado = $libro_o . '--' . $nmargi1;
$anio_marginacion = (int)$libro_o;
$busquedalf = $nombre_el . ' ' . $nombre_ella . ' ' . $n_partida;
$tipo_marginacion = 'MODIFICACION';
$estado = 'DIGITADO';

// Verificar ID único
$check = $conn->prepare("SELECT id FROM margi WHERE libro_nmargi_concat = ?");
if ($check) {
    $check->bind_param("s", $concatenado);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        responder('error', 'El ID digital ya existe: ' . $concatenado);
    }
    $check->close();
}

// Obtener correlativo
$correlativo_anual = 1;
$stmt_corr = $conn->prepare("SELECT MAX(correlativo_anual) as max_corr FROM margi WHERE anio_marginacion = ?");
if ($stmt_corr) {
    $stmt_corr->bind_param("i", $anio_marginacion);
    $stmt_corr->execute();
    $row = $stmt_corr->get_result()->fetch_assoc();
    $correlativo_anual = ($row['max_corr'] ?? 0) + 1;
    $stmt_corr->close();
}

// =====================================================
// INSERTAR
// =====================================================
$sql = "INSERT INTO margi SET
    anio_marginacion = ?,
    correlativo_anual = ?,
    NMargi1 = ?,
    TxtMargi1 = ?,
    AnioP = ?,
    LibroP = ?,
    NPartida = ?,
    FolioO = ?,
    TomoP = ?,
    TipoP = ?,
    lugar = ?,
    num_tramite = ?,
    busquedalf = ?,
    FechaC = ?,
    HoraC = ?,
    Iniciales1 = ?,
    TipoMargi = ?,
    LibroO = ?,
    estado = ?,
    usuario_creo = ?,
    libro_nmargi_concat = ?,
    fechaevento = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    responder('error', 'Error preparando consulta: ' . $conn->error);
}

$stmt->bind_param(
    "iissssssssssssssssssss",
    $anio_marginacion,
    $correlativo_anual,
    $nmargi1,
    $texto_final,
    $anio_p,
    $libro_p,
    $n_partida,
    $folio_o,
    $tomo_p,
    $iniciales_partida,
    $lugar,
    $num_tramite,
    $busquedalf,
    $fecha_actual,
    $hora_actual,
    $usuario,
    $tipo_marginacion,
    $libro_o,
    $estado,
    $usuario,
    $concatenado,
    $fecha_evento
);

if ($stmt->execute()) {
    responder('success', 'Marginación guardada exitosamente', ['id' => $concatenado]);
} else {
    responder('error', 'Error al guardar: ' . $stmt->error);
}
?>
